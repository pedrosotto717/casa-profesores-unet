<?php declare(strict_types=1);

namespace App\Services;

use App\Models\File;
use App\Models\Setting;
use App\Support\R2Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class InstitutionConfigService
{
    private const CONFIG_KEY = 'institution_info';
    private const SOCIAL_MEDIA_KEY = 'social_media_config';

    /**
     * Get institution configuration with default values if not exists.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $config = $this->getRawConfig();
        
        // Transform file_id to image_url for each member
        if (isset($config['members']) && is_array($config['members'])) {
            $config['members'] = $this->enrichMembersWithUrls($config['members']);
        }

        // Include social media configuration
        $config['social_media'] = $this->getSocialMediaConfig();

        return $config;
    }

    /**
     * Get raw configuration from database without enrichment.
     *
     * @return array<string, mixed>
     */
    private function getRawConfig(): array
    {
        $setting = Setting::where('key', self::CONFIG_KEY)->first();

        if (!$setting) {
            return $this->getDefaultConfig();
        }

        return $setting->value;
    }

    /**
     * Update institution configuration.
     *
     * @param array<string, mixed> $data
     * @param array<string, UploadedFile> $memberImages
     * @param int $userId
     * @return array<string, mixed>
     */
    public function updateConfig(array $data, array $memberImages = [], int $userId = 0): array
    {
        return DB::transaction(function () use ($data, $memberImages, $userId) {
            // Get existing members to track old file IDs for deletion
            $existingConfig = $this->getRawConfig();
            $existingMembers = $existingConfig['members'] ?? [];
            $existingFileIds = $this->extractFileIds($existingMembers);

            $configData = [
                'mission' => $data['mission'],
                'vision' => $data['vision'],
                'members' => $this->processMembers($data['members'] ?? [], $memberImages, $userId),
            ];

            // Get new file IDs
            $newFileIds = $this->extractFileIds($configData['members']);

            // Delete old files that are no longer referenced
            $filesToDelete = array_diff($existingFileIds, $newFileIds);
            $this->deleteFilesByIds($filesToDelete);

            $setting = Setting::updateOrCreate(
                ['key' => self::CONFIG_KEY],
                ['value' => $configData]
            );

            // Update social media configuration if provided
            if (isset($data['social_media'])) {
                $this->updateSocialMediaConfig($data['social_media']);
            }

            // Enrich with URLs for response
            $responseConfig = $setting->value;
            $responseConfig['members'] = $this->enrichMembersWithUrls($responseConfig['members']);
            $responseConfig['social_media'] = $this->getSocialMediaConfig();

            return $responseConfig;
        });
    }

    /**
     * Process members data and handle image uploads.
     *
     * @param array<int, array<string, mixed>> $members
     * @param array<string, UploadedFile> $memberImages
     * @param int $userId
     * @return array<int, array<string, mixed>>
     */
    private function processMembers(array $members, array $memberImages, int $userId): array
    {
        // Get existing configuration to preserve file_id
        $existingConfig = $this->getRawConfig();
        $existingMembersMap = [];
        
        if (isset($existingConfig['members'])) {
            foreach ($existingConfig['members'] as $existingMember) {
                $existingMembersMap[$existingMember['id']] = $existingMember;
            }
        }

        $processedMembers = [];

        foreach ($members as $member) {
            $memberId = $member['id'];
            $existingMember = $existingMembersMap[$memberId] ?? null;
            $existingFileId = $existingMember['file_id'] ?? null;

            // Handle new image upload
            if (isset($memberImages[$memberId])) {
                $file = $memberImages[$memberId];

                // Delete old file if exists
                if ($existingFileId) {
                    $oldFile = File::find($existingFileId);
                    if ($oldFile) {
                        R2Storage::deleteFile($oldFile);
                    }
                }

                // Upload new image to R2
                $fileRecord = R2Storage::putPublicWithRecord(
                    $file,
                    $userId,
                    'image',
                    "Board member: {$member['name']}",
                    "Profile image for board member {$member['name']}"
                );

                $processedMembers[] = [
                    'id' => $memberId,
                    'name' => $member['name'],
                    'role' => $member['role'],
                    'file_id' => $fileRecord->id,
                    'image_url' => $fileRecord->url, // Store URL for convenience
                ];
            } else {
                // Keep existing file_id from database, not from request
                $processedMembers[] = [
                    'id' => $memberId,
                    'name' => $member['name'],
                    'role' => $member['role'],
                    'file_id' => $existingFileId,
                    'image_url' => $existingMember['image_url'] ?? null,
                ];
            }
        }

        return $processedMembers;
    }

    /**
     * Enrich members array with image URLs from file_id.
     *
     * @param array<int, array<string, mixed>> $members
     * @return array<int, array<string, mixed>>
     */
    private function enrichMembersWithUrls(array $members): array
    {
        return array_map(function ($member) {
            $fileId = $member['file_id'] ?? null;
            
            if ($fileId) {
                $file = File::find($fileId);
                if ($file) {
                    $member['image_url'] = $file->url;
                }
            }
            
            // Ensure image_url exists even if file_id is missing
            if (!isset($member['image_url']) && isset($member['image_url_legacy'])) {
                $member['image_url'] = $member['image_url_legacy'];
            }

            return $member;
        }, $members);
    }

    /**
     * Extract file IDs from members array.
     *
     * @param array<int, array<string, mixed>> $members
     * @return array<int>
     */
    private function extractFileIds(array $members): array
    {
        $fileIds = [];
        
        foreach ($members as $member) {
            if (isset($member['file_id']) && $member['file_id']) {
                $fileIds[] = (int) $member['file_id'];
            }
        }
        
        return array_filter($fileIds);
    }

    /**
     * Delete files by their IDs.
     *
     * @param array<int> $fileIds
     * @return void
     */
    private function deleteFilesByIds(array $fileIds): void
    {
        foreach ($fileIds as $fileId) {
            $file = File::find($fileId);
            if ($file) {
                R2Storage::deleteFile($file);
            }
        }
    }

    /**
     * Get default configuration structure.
     *
     * @return array<string, mixed>
     */
    private function getDefaultConfig(): array
    {
        return [
            'mission' => 'Nuestra misión es...',
            'vision' => 'Nuestra visión es...',
            'members' => [],
        ];
    }

    /**
     * Get social media configuration.
     *
     * @return array<string, string|null>
     */
    private function getSocialMediaConfig(): array
    {
        $setting = Setting::where('key', self::SOCIAL_MEDIA_KEY)->first();

        if (!$setting) {
            return $this->getDefaultSocialMediaConfig();
        }

        return $setting->value;
    }

    /**
     * Update social media configuration.
     *
     * @param array<string, string|null> $data
     * @return void
     */
    private function updateSocialMediaConfig(array $data): void
    {
        $socialMediaData = [
            'facebook' => $data['facebook'] ?? null,
            'instagram' => $data['instagram'] ?? null,
            'twitter' => $data['twitter'] ?? null,
            'email' => $data['email'] ?? null,
        ];

        Setting::updateOrCreate(
            ['key' => self::SOCIAL_MEDIA_KEY],
            ['value' => $socialMediaData]
        );
    }

    /**
     * Get default social media configuration.
     *
     * @return array<string, string|null>
     */
    private function getDefaultSocialMediaConfig(): array
    {
        return [
            'facebook' => null,
            'instagram' => null,
            'twitter' => null,
            'email' => null,
        ];
    }
}

