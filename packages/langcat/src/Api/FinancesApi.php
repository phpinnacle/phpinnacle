<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\Finances\CreateInstallmentCollectionRequest;
use PHPinnacle\Langcat\Request\Finances\CreateInstallmentRequest;
use PHPinnacle\Langcat\Request\Finances\CreateInstallmentTagRequest;
use PHPinnacle\Langcat\Request\Finances\ListCollectionInstallmentsRequest;
use PHPinnacle\Langcat\Request\Finances\ListInstallmentCollectionsRequest;
use PHPinnacle\Langcat\Request\Finances\ListInstallmentTagsRequest;
use PHPinnacle\Langcat\Request\Finances\ListStudentInstallmentsRequest;
use PHPinnacle\Langcat\Request\Finances\UpdateInstallmentCollectionRequest;
use PHPinnacle\Langcat\Request\Finances\UpdateInstallmentRequest;
use PHPinnacle\Langcat\Request\Finances\UpdateInstallmentTagRequest;
use PHPinnacle\Langcat\Request\Finances\UpdateInstallmentTagsRequest;
use PHPinnacle\Langcat\Request\Finances\WriteStudentInstallmentRequest;
use PHPinnacle\Langcat\Response\Finances\InstallmentCollectionResponse;
use PHPinnacle\Langcat\Response\Finances\InstallmentCollectionsResponse;
use PHPinnacle\Langcat\Response\Finances\InstallmentResponse;
use PHPinnacle\Langcat\Response\Finances\InstallmentsResponse;
use PHPinnacle\Langcat\Response\Finances\InstallmentTagResponse;
use PHPinnacle\Langcat\Response\Finances\InstallmentTagsResponse;
use PHPinnacle\Langcat\Response\Shared\IdResponse;
use PHPinnacle\Langcat\Support\ResourcePath;
use PHPinnacle\Langcat\Support\Transport;

final readonly class FinancesApi
{
    private const string ROOT = '/finances/settings/installments';

    public function __construct(
        private Transport $transport,
    ) {}

    public function installmentCollections(?ListInstallmentCollectionsRequest $request = null): InstallmentCollectionsResponse
    {
        return InstallmentCollectionsResponse::fromArray($this->transport->send(
            'GET',
            self::ROOT . '/collections',
            $request?->toQuery() ?? [],
        ));
    }

    public function installmentCollection(int $id): InstallmentCollectionResponse
    {
        return InstallmentCollectionResponse::fromArray($this->transport->send('GET', $this->collectionPath($id)));
    }

    public function createInstallmentCollection(CreateInstallmentCollectionRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'POST',
            self::ROOT . '/collections',
            body: $request->toArray(),
        ));
    }

    public function updateInstallmentCollection(int $id, UpdateInstallmentCollectionRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'PATCH',
            $this->collectionPath($id),
            body: $request->toArray(),
        ));
    }

    public function deleteInstallmentCollection(int $id): IdResponse
    {
        return IdResponse::fromArray($this->transport->send('DELETE', $this->collectionPath($id)));
    }

    public function collectionInstallments(
        int $collectionId,
        ?ListCollectionInstallmentsRequest $request = null,
    ): InstallmentsResponse {
        return InstallmentsResponse::fromArray($this->transport->send(
            'GET',
            $this->collectionItemsPath($collectionId),
            $request?->toQuery() ?? [],
        ));
    }

    public function collectionInstallment(int $collectionId, int $installmentId): InstallmentResponse
    {
        return InstallmentResponse::fromArray($this->transport->send('GET', $this->collectionItemPath(
            $collectionId,
            $installmentId,
        )));
    }

    public function createCollectionInstallment(int $collectionId, CreateInstallmentRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'POST',
            $this->collectionItemsPath($collectionId),
            body: $request->toArray(),
        ));
    }

    public function updateCollectionInstallment(
        int $collectionId,
        int $installmentId,
        UpdateInstallmentRequest $request,
    ): IdResponse {
        return IdResponse::fromArray($this->transport->send(
            'PATCH',
            $this->collectionItemPath($collectionId, $installmentId),
            body: $request->toArray(),
        ));
    }

    public function deleteCollectionInstallment(int $collectionId, int $installmentId): IdResponse
    {
        return IdResponse::fromArray($this->transport->send('DELETE', $this->collectionItemPath(
            $collectionId,
            $installmentId,
        )));
    }

    public function updateCollectionInstallmentTags(
        int $collectionId,
        int $installmentId,
        UpdateInstallmentTagsRequest $request,
    ): IdResponse {
        return IdResponse::fromArray($this->transport->send(
            'PATCH',
            $this->collectionItemPath($collectionId, $installmentId) . '/tags',
            body: $request->toArray(),
        ));
    }

    public function installmentTags(?ListInstallmentTagsRequest $request = null): InstallmentTagsResponse
    {
        return InstallmentTagsResponse::fromArray($this->transport->send(
            'GET',
            self::ROOT . '/tags',
            $request?->toQuery() ?? [],
        ));
    }

    public function installmentTag(int $id): InstallmentTagResponse
    {
        return InstallmentTagResponse::fromArray($this->transport->send('GET', $this->tagPath($id)));
    }

    public function createInstallmentTag(CreateInstallmentTagRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send('POST', self::ROOT . '/tags', body: $request->toArray()));
    }

    public function updateInstallmentTag(int $id, UpdateInstallmentTagRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send('PATCH', $this->tagPath($id), body: $request->toArray()));
    }

    public function studentInstallments(
        int $groupId,
        int $studentId,
        ?ListStudentInstallmentsRequest $request = null,
    ): InstallmentsResponse {
        return InstallmentsResponse::fromArray($this->transport->send(
            'GET',
            $this->studentInstallmentsPath($groupId, $studentId),
            $request?->toQuery() ?? [],
        ));
    }

    public function studentInstallment(int $groupId, int $studentId, int $installmentId): InstallmentResponse
    {
        return InstallmentResponse::fromArray($this->transport->send('GET', $this->studentInstallmentPath(
            $groupId,
            $studentId,
            $installmentId,
        )));
    }

    public function createStudentInstallment(
        int $groupId,
        int $studentId,
        WriteStudentInstallmentRequest $request,
    ): IdResponse {
        return IdResponse::fromArray($this->transport->send(
            'POST',
            $this->studentInstallmentsPath($groupId, $studentId),
            body: $request->toArray(),
        ));
    }

    public function updateStudentInstallment(
        int $groupId,
        int $studentId,
        int $installmentId,
        WriteStudentInstallmentRequest $request,
    ): IdResponse {
        return IdResponse::fromArray($this->transport->send(
            'PATCH',
            $this->studentInstallmentPath($groupId, $studentId, $installmentId),
            body: $request->toArray(),
        ));
    }

    public function updateStudentInstallmentTags(
        int $groupId,
        int $studentId,
        int $installmentId,
        UpdateInstallmentTagsRequest $request,
    ): IdResponse {
        return IdResponse::fromArray($this->transport->send(
            'PATCH',
            $this->studentInstallmentPath($groupId, $studentId, $installmentId) . '/tags',
            body: $request->toArray(),
        ));
    }

    private function collectionPath(int $id): string
    {
        return ResourcePath::id(ltrim(self::ROOT . '/collections', '/'), $id);
    }

    private function collectionItemsPath(int $collectionId): string
    {
        return $this->collectionPath($collectionId) . '/items';
    }

    private function collectionItemPath(int $collectionId, int $installmentId): string
    {
        return ResourcePath::id(ltrim($this->collectionItemsPath($collectionId), '/'), $installmentId);
    }

    private function tagPath(int $id): string
    {
        return ResourcePath::id(ltrim(self::ROOT . '/tags', '/'), $id);
    }

    private function studentInstallmentsPath(int $groupId, int $studentId): string
    {
        return (
            ResourcePath::id(ltrim(ResourcePath::id('groups', $groupId) . '/students', '/'), $studentId)
            . '/installments'
        );
    }

    private function studentInstallmentPath(int $groupId, int $studentId, int $installmentId): string
    {
        return ResourcePath::id(ltrim($this->studentInstallmentsPath($groupId, $studentId), '/'), $installmentId);
    }
}
