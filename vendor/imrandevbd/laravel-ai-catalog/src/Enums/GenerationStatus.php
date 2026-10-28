<?php

namespace ImranDevBd\AiCatalog\Enums;

enum GenerationStatus: string
{
    case Pending = 'pending';
    case Generating = 'generating';
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Published, self::Failed], true);
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::Draft, self::InReview, self::Rejected], true);
    }

    public function canApprove(): bool
    {
        return in_array($this, [self::Draft, self::InReview], true);
    }
}
