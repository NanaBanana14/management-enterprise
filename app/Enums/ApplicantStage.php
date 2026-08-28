<?php

namespace App\Enums;

enum ApplicantStage: string
{
    case Applied = 'applied';
    case Screening = 'screening';
    case Interview = 'interview';
    case Assessment = 'assessment';
    case Offer = 'offer';
    case Hired = 'hired';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Applied',
            self::Screening => 'Screening',
            self::Interview => 'Interview',
            self::Assessment => 'Assessment',
            self::Offer => 'Offer',
            self::Hired => 'Hired',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * The forward path through the pipeline. Used to stop a stage move
     * from skipping ahead or reviving a rejected applicant.
     */
    public static function order(): array
    {
        return [self::Applied, self::Screening, self::Interview, self::Assessment, self::Offer, self::Hired];
    }
}
