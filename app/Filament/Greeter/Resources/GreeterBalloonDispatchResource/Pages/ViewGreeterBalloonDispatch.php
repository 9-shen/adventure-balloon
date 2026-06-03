<?php

namespace App\Filament\Greeter\Resources\GreeterBalloonDispatchResource\Pages;

use App\Filament\Greeter\Resources\GreeterBalloonDispatchResource;
use App\Filament\Greeter\Resources\GreeterBalloonDispatchResource\Pages\ListGreeterBalloonDispatches;
use App\Models\BalloonDispatch;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;

class ViewGreeterBalloonDispatch extends ViewRecord
{
    protected static string $resource = GreeterBalloonDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_image')
                ->label('Download Image')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => $this->getRecord()->hasImage()
                    ? route('balloon-dispatch.image.download', $this->getRecord())
                    : null
                )
                ->hidden(fn () => ! $this->getRecord()->hasImage())
                ->openUrlInNewTab(false),

            Action::make('back')
                ->label('Back to List')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ListGreeterBalloonDispatches::getUrl()),
        ];
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->columns(2)
            ->components([
                Section::make('Dispatch Details')
                    ->description('Balloon dispatch information for this day.')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('dispatch_date')
                            ->label('Dispatch Date')
                            ->date('d/m/Y')
                            ->weight('bold'),

                        TextEntry::make('content')
                            ->label('Operational Notes')
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('creator.name')
                            ->label('Posted By')
                            ->placeholder('—'),

                        TextEntry::make('created_at')
                            ->label('Posted At')
                            ->dateTime('d/m/Y H:i'),
                    ]),

                Section::make('Dispatch Image')
                    ->description('Photo attached to this dispatch.')
                    ->columnSpan(1)
                    ->schema([
                        \Filament\Infolists\Components\SpatieMediaLibraryImageEntry::make('image')
                            ->hiddenLabel()
                            ->collection('balloon-dispatch-images')
                            ->height(300)
                            ->extraImgAttributes(['class' => 'rounded-lg object-cover w-full'])
                            ->placeholder('No image uploaded for this dispatch.'),
                    ]),
            ]);
    }
}
