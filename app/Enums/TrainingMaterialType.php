<?php

namespace App\Enums;

enum TrainingMaterialType: string
{
    case Text = 'text';
    case Video = 'video';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Video => 'Video',
            self::Document => 'Document',
        };
    }
}
