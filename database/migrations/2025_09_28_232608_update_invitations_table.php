<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            // Add name field for the person being invited
            $table->string('name')->after('email');
            
            // Add rejection_reason field
            $table->text('rejection_reason')->nullable()->after('reviewed_at');
        });

        // Drop foreign key constraint first, then the column
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropForeign(['invitee_user_id']);
            $table->dropColumn('invitee_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('rejection_reason');
        });

        // Add back the invitee_user_id column and foreign key
        Schema::table('invitations', function (Blueprint $table) {
            $table->foreignId('invitee_user_id')->nullable()->constrained('users');
        });
    }
};
