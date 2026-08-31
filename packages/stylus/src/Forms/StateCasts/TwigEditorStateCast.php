<?php

namespace PHPinnacle\Stylus\Forms\StateCasts;

use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use InvalidArgumentException;
use PHPinnacle\Stylus\Forms\TwigEditor;
use PHPinnacle\Stylus\Twig\TwigDocumentSerializer;

class TwigEditorStateCast implements StateCast
{
    public function __construct(
        private readonly TwigEditor $twigEditor,
        private readonly TwigDocumentSerializer $serializer,
    ) {}

    /** @return array{version: int, document: array<string, mixed>, template: string} */
    public function get(mixed $state): array
    {
        $document = $this->twigEditor->compileStructuredConditions(
            $this->normalizeDocument($state),
        );

        return [
            'version' => TwigEditor::DOCUMENT_VERSION,
            'document' => $document,
            'template' => $this->serializer->serialize($document),
        ];
    }

    /** @return array<string, mixed> */
    public function set(mixed $state): array
    {
        return $this->twigEditor->compileStructuredConditions(
            $this->normalizeDocument($state),
        );
    }

    /** @return array<string, mixed> */
    private function normalizeDocument(mixed $state): array
    {
        if ($state === null) {
            $state = TwigEditor::emptyDocument();
        }

        if (is_array($state) && array_key_exists('version', $state)) {
            if (($state['version'] ?? null) !== TwigEditor::DOCUMENT_VERSION) {
                throw new InvalidArgumentException('Unsupported Twig editor document version.');
            }

            $state = $state['document'] ?? null;
        }

        if (!is_array($state) || ($state['type'] ?? null) !== 'doc') {
            throw new InvalidArgumentException('Twig editor state must contain a ProseMirror doc.');
        }

        return $this->twigEditor
            ->getTipTapEditor()
            ->setContent($state)
            ->getDocument();
    }
}
