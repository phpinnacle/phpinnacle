<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\Documents\ListDocumentTemplatesRequest;
use PHPinnacle\Langcat\Response\Documents\DocumentTemplateResponse;
use PHPinnacle\Langcat\Response\Documents\DocumentTemplatesResponse;
use PHPinnacle\Langcat\Support\ResourcePath;
use PHPinnacle\Langcat\Support\Transport;

final readonly class DocumentsApi
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function templates(?ListDocumentTemplatesRequest $request = null): DocumentTemplatesResponse
    {
        return DocumentTemplatesResponse::fromArray($this->transport->send(
            'GET',
            '/documents/templates',
            $request?->toQuery() ?? [],
        ));
    }

    public function template(int $templateId): DocumentTemplateResponse
    {
        return DocumentTemplateResponse::fromArray($this->transport->send(
            'GET',
            ResourcePath::id('documents/templates', $templateId),
        ));
    }
}
