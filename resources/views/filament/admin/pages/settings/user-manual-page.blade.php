<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Sidebar Navigation (Table of Contents) -->
        <div class="lg:col-span-3">
            <aside class="sticky top-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-4 px-2">Table of Contents</p>
                <nav class="space-y-1">
                    @foreach($this->getTableOfContents() as $item)
                        <a href="#{{ $item['id'] }}" 
                           class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800/50 transition-colors duration-150 ease-in-out">
                            {{ $item['title'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>
        </div>

        <!-- Document Content -->
        <div class="lg:col-span-9">
            <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="user-manual-content prose max-w-none dark:prose-invert">
                    {!! $this->getMarkdownContent() !!}
                </div>
            </div>
        </div>
    </div>

    <!-- Extra styles for beautiful typography, tables, and alerts -->
    <style>
        .user-manual-content h1 {
            display: none; /* Hide main h1 since page title serves this purpose */
        }
        .user-manual-content h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: rgb(17 24 39);
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgb(229 231 235);
            scroll-margin-top: 2rem;
        }
        .dark .user-manual-content h2 {
            color: rgb(243 244 246);
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        .user-manual-content h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: rgb(31 41 55);
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            scroll-margin-top: 2rem;
        }
        .dark .user-manual-content h3 {
            color: rgb(229 231 235);
        }
        .user-manual-content p {
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
            line-height: 1.625;
            color: rgb(75 85 99);
        }
        .dark .user-manual-content p {
            color: rgb(156 163 175);
        }
        .user-manual-content ul {
            list-style-type: disc;
            padding-left: 1.5rem;
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
            color: rgb(75 85 99);
        }
        .dark .user-manual-content ul {
            color: rgb(156 163 175);
        }
        .user-manual-content ol {
            list-style-type: decimal;
            padding-left: 1.5rem;
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
            color: rgb(75 85 99);
        }
        .dark .user-manual-content ol {
            color: rgb(156 163 175);
        }
        .user-manual-content li {
            margin-top: 0.375rem;
            margin-bottom: 0.375rem;
        }
        .user-manual-content strong {
            font-weight: 600;
            color: rgb(17 24 39);
        }
        .dark .user-manual-content strong {
            color: rgb(243 244 246);
        }
        .user-manual-content code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.875em;
            background-color: rgb(243 244 246);
            color: rgb(220 38 38);
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
        }
        .dark .user-manual-content code {
            background-color: rgba(255, 255, 255, 0.1);
            color: rgb(248 113 113);
        }
        .user-manual-content pre {
            background-color: rgb(243 244 246);
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        .dark .user-manual-content pre {
            background-color: rgb(17 24 39);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .user-manual-content pre code {
            background-color: transparent;
            color: inherit;
            padding: 0;
            border-radius: 0;
        }
        .user-manual-content table {
            width: 100%;
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        .user-manual-content th {
            background-color: rgb(249 250 251);
            color: rgb(17 24 39);
            font-weight: 600;
            text-align: left;
            padding: 0.75rem 1rem;
            border-bottom: 2px solid rgb(229 231 235);
        }
        .dark .user-manual-content th {
            background-color: rgb(17 24 39);
            color: rgb(243 244 246);
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        .user-manual-content td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgb(243 244 246);
            color: rgb(75 85 99);
        }
        .dark .user-manual-content td {
            border-bottom-color: rgba(255, 255, 255, 0.05);
            color: rgb(156 163 175);
        }
        .user-manual-content tr:hover {
            background-color: rgb(253 253 253);
        }
        .dark .user-manual-content tr:hover {
            background-color: rgba(255, 255, 255, 0.02);
        }
        .user-manual-content hr {
            margin-top: 2rem;
            margin-bottom: 2rem;
            border: 0;
            border-top: 1px solid rgb(229 231 235);
        }
        .dark .user-manual-content hr {
            border-top-color: rgba(255, 255, 255, 0.1);
        }
    </style>
</x-filament-panels::page>
