<?php declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class EmailVerificationNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $verificationUrl)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $fromAddress = config('mail.from.address', 'noreply@casadelprofesor.unet.edu.ve');
        $fromName = config('mail.from.name', 'Casa del Profesor UNET');

        return $this
            ->from($fromAddress, $fromName)
            ->subject('Verificación de Correo Electrónico - Casa del Profesor UNET')
            ->view('emails.email-verification')
            ->with([
                'user' => $this->user,
                'verificationUrl' => $this->verificationUrl,
            ]);
    }
}
