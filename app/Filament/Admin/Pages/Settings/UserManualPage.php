<?php

namespace App\Filament\Admin\Pages\Settings;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserManualPage extends Page
{
    protected string $view = 'filament.admin.pages.settings.user-manual-page';

    public static function getNavigationIcon(): string|null
    {
        return 'heroicon-o-book-open';
    }

    public static function getNavigationLabel(): string
    {
        return 'User Manual';
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
    }

    public function getTitle(): \Illuminate\Contracts\Support\Htmlable|string
    {
        return 'Operations & Portal Manual';
    }

    public static function getNavigationGroup(): string|null
    {
        return 'Settings';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return (bool) ($user?->hasRole('super_admin') || $user?->hasRole('admin'));
    }

    public function getTableOfContents(): array
    {
        $filePath = base_path('docs/USER_MANUAL.md');
        if (!file_exists($filePath)) {
            return [];
        }

        $lines = file($filePath);
        $toc = [];

        foreach ($lines as $line) {
            if (preg_match('/^##\s+(.+)$/', trim($line), $matches)) {
                $title = trim($matches[1]);
                // Ignore the Table of Contents header itself
                if (str_contains($title, 'Table of Contents')) {
                    continue;
                }
                // Strip emojis or icons for ID generation
                $cleanTitle = preg_replace('/^[\x{1F300}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1F1E0}-\x{1F1FF}]\s+/u', '', $title);
                $cleanTitle = trim(preg_replace('/^[0-9.]+\s+/', '', $cleanTitle)); // Strip number prefixes like "1. " or "2.1 "
                $id = Str::slug($cleanTitle);
                $toc[] = [
                    'id' => $id,
                    'title' => $title,
                ];
            }
        }

        return $toc;
    }

    public function getMarkdownContent(): string
    {
        $filePath = base_path('docs/USER_MANUAL.md');

        if (!file_exists($filePath)) {
            return '<p class="text-danger-600 dark:text-danger-400">User Manual file not found at docs/USER_MANUAL.md</p>';
        }

        $content = file_get_contents($filePath);
        
        $html = Str::markdown($content);
        
        // Find all <h2>Title</h2> and replace with <h2 id="slug">Title</h2>
        $html = preg_replace_callback('/<h2>(.*?)<\/h2>/i', function($matches) {
            $text = strip_tags($matches[1]);
            // Strip emojis
            $cleanText = preg_replace('/[\x{1F300}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1F1E0}-\x{1F1FF}]/u', '', $text);
            $cleanText = trim(preg_replace('/^[0-9.]+\s+/', '', $cleanText)); // Strip number prefixes like "1. " or "2.1 "
            $id = Str::slug($cleanText);
            return '<h2 id="' . $id . '" class="scroll-mt-6">' . $matches[1] . '</h2>';
        }, $html);
        
        // Convert [!NOTE] to custom HTML note alert
        $html = preg_replace_callback('/<blockquote>\s*<p>\s*\[!NOTE\]\s*(.*?)<\/p>\s*<\/blockquote>/is', function($matches) {
            return '<div class="my-4 p-4 rounded-lg border-l-4 border-primary-500 bg-primary-50/50 dark:bg-primary-950/20 text-gray-700 dark:text-gray-300">
                <div class="flex items-center gap-2 font-semibold text-primary-600 dark:text-primary-400 mb-1">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Note
                </div>
                <div>' . $matches[1] . '</div>
            </div>';
        }, $html);

        // Convert [!WARNING] to custom HTML warning alert
        $html = preg_replace_callback('/<blockquote>\s*<p>\s*\[!WARNING\]\s*(.*?)<\/p>\s*<\/blockquote>/is', function($matches) {
            return '<div class="my-4 p-4 rounded-lg border-l-4 border-amber-500 bg-amber-50/50 dark:bg-amber-950/20 text-gray-700 dark:text-gray-300">
                <div class="flex items-center gap-2 font-semibold text-amber-600 dark:text-amber-400 mb-1">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Warning
                </div>
                <div>' . $matches[1] . '</div>
            </div>';
        }, $html);

        return $html;
    }
}
