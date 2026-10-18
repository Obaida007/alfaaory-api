<?php

namespace App\Support;

use App\Http\Middleware\UnifiedJsonResponse;
use Dedoc\Scramble\Support\Generator\MediaType;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

/**
 * Documents the { data, meta, message } envelope that {@see UnifiedJsonResponse} adds to every API response,
 * so the generated TypeScript types match what the API really returns.
 */
class ApiEnvelopeDocumentTransformer
{
    public function __invoke(OpenApi $openApi): void
    {
        foreach ($openApi->paths as $path) {
            foreach ($path->operations as $operation) {
                foreach ($operation->responses ?? [] as $response) {
                    if ($response instanceof Response) {
                        $this->wrap($response);
                    }
                }
            }
        }
    }

    protected function wrap(Response $response): void
    {
        foreach ($response->content as $media) {
            $schema = $media instanceof MediaType ? $media->schema : $media;

            if (! $schema instanceof Schema || ! $schema->type instanceof ObjectType || ! isset($schema->type->properties['data'])) {
                continue;
            }

            $type = $schema->type;

            // Paginated responses: we send { data, meta } without `links` (see AppServiceProvider).
            unset($type->properties['links']);
            if (($meta = $type->properties['meta'] ?? null) instanceof ObjectType) {
                unset($meta->properties['links'], $meta->properties['path']);
                $meta->setRequired(array_values(array_diff($meta->required, ['links', 'path'])));
            }

            if (! isset($type->properties['meta'])) {
                $type->addProperty('meta', (new ObjectType)->nullable(true)->setDescription('Pagination and extra info, or null.'));
            }
            if (! isset($type->properties['message'])) {
                $type->addProperty('message', (new StringType)->nullable(true)->setDescription('Human-readable Arabic message, or null.'));
            }

            $type->setRequired(array_values(array_unique([...array_diff($type->required, ['links']), 'data', 'meta', 'message'])));
        }
    }
}
