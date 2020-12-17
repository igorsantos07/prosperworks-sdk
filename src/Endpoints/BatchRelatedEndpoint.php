<?php

namespace ProsperWorks\Endpoints;

use GuzzleHttp\Client;
use ProsperWorks\SubResources\Relation;

class BatchRelatedEndpoint extends BaseEndpoint
{

    /** @noinspection PhpMissingParentConstructorInspection
     * @param string $uri
     * @param Client $client
     */
    public function __construct(string $uri, Client $client)
    {
        $this->uri = $uri;
        $this->client = $client;
    }

    /** @noinspection PhpInconsistentReturnPointsInspection */
    /**
     * Prepares a generator given a list of Relations, indexed by the correct path to be requested.
     * @param \Traversable|Relation[] $relations List of Relation objects, indexed by the main resource's id
     * @return \Generator
     */
    private function listGenerator($relations)
    {
        return (function ($relations) {
            foreach ($relations as $owner => $related) {
				foreach ($related as $r) {
					yield "$owner/related" => ['json' => ['resource' => [ 'id' => $r['id'], 'type' => $r['type']]]];
				}
            }
        })($relations);
    }

    /**
     * Creates many relations at once.
     * @param \Traversable|Relation[] $relations List of Relation objects, indexed by the main resource's id
     * @return object[]
     */
    public function create($relations)
    {
		//TODO: replace with is_iterable() at PHP 7.1
		return $this->requestMany('post', $this->listGenerator($relations));
    }

    /**
     * Creates many relations at once.
     * @param \Traversable|Relation[] $relations List of Relation objects, indexed by the main resource's id
     * @return bool[]
     */
    public function delete($relations)
    {
        return $this->requestMany('delete', $this->listGenerator($relations));
    }
}
