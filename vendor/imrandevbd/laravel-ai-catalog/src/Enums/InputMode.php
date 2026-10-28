<?php

namespace ImranDevBd\AiCatalog\Enums;

enum InputMode: string
{
    case Keyword = 'keyword';
    case Text = 'text';
    case Image = 'image';
    case Url = 'url';
    case Bulk = 'bulk';
    case Voice = 'voice';
    case Document = 'document';
    case Api = 'api';
}
