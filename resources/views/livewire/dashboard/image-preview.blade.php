<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Document;

new class extends Component {
    public ?Document $previewingImage = null;

    #[On('preview-image')]
    public function load(int $id): void
    {
        $this->previewingImage = Document::findOrFail($id);
        $this->modal('image-preview')->show();
    }

    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
};
?>

<flux:modal name="image-preview" class="max-w-5xl !p-0 overflow-hidden bg-white dark:bg-zinc-900 border-none">
    @if($previewingImage)
        <div class="relative w-full aspect-video md:aspect-auto md:h-[80vh] flex flex-col items-center justify-center bg-black">
            <img 
                src="{{ asset('storage/' . $previewingImage->path) }}" 
                class="max-w-full max-h-full object-contain shadow-2xl"
                alt="{{ $previewingImage->name }}"
            >
            
            <div class="absolute bottom-6 left-0 right-0 flex flex-col items-center gap-2 bg-gradient-to-t from-black/60 to-transparent pb-4 pt-10">
                <span class="text-white text-lg font-black tracking-tight drop-shadow-lg">
                    {{ $previewingImage->name }}
                </span>
                <span class="text-zinc-300 text-xs font-bold uppercase tracking-widest drop-shadow-md">
                    {{ $this->formatBytes($previewingImage->size_bytes) }} • {{ $previewingImage->created_at->format('d/m/Y') }}
                </span>
            </div>
        </div>
    @endif
</flux:modal>
