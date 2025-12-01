<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

trait ResolvesFormRequests
{
    private const REQUEST_NAMESPACE_BASE = 'App\\Http\\Requests\\';

    private const PIVOT_NAMESPACE_SUFFIX = '\\Pivot\\';

    private const REQUEST_CLASS_SUFFIX = 'Request';

    private const PIVOT_ROUTE_PREFIX = 'pivot.';

    private const UNKNOWN_ACTION = 'unknown';

    private const ROUTE_PATH_PARAMETER = 'path';

    private Container $container;

    private LoggerInterface $logger;

    private ?Route $cachedRoute = null;

    public function resolveFormRequest(mixed $fallbackRequest = null): object
    {
        try {
            $this->initializeDependencies();

            $currentRequest = $this->getCurrentRequest();
            $currentAction = $this->getCurrentAction();

            $this->logRequestResolution($currentAction);

            $requestClassName = $this->determineRequestClassName($currentRequest, $currentAction);

            if ($this->isValidRequestClass($requestClassName)) {
                $formRequest = $this->instantiateRequestClass($requestClassName);
                $this->validateFormRequest($formRequest, $currentRequest);

                return $formRequest;
            }

            return $this->resolveFallbackRequest($fallbackRequest, $currentAction);

        } catch (Throwable $exception) {
            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            $this->handleResolutionError($exception, $currentAction ?? self::UNKNOWN_ACTION);

            throw new InvalidArgumentException(
                'Failed to resolve form request: '.$exception->getMessage(),
                previous: $exception,
            );
        }
    }

    private function validateFormRequest(object $formRequest, Request $currentRequest): void
    {
        if (method_exists($formRequest, 'validateResolved')) {
            $formRequest->query = $currentRequest->query;
            $formRequest->request = $currentRequest->request;
            $formRequest->attributes = $currentRequest->attributes;
            $formRequest->cookies = $currentRequest->cookies;
            $formRequest->files = $currentRequest->files;
            $formRequest->server = $currentRequest->server;
            $formRequest->headers = $currentRequest->headers;

            $formRequest->validateResolved();
        }
    }

    private function initializeDependencies(): void
    {
        if (! isset($this->container)) {
            $this->container = app(Container::class);
        }

        if (! isset($this->logger)) {
            $this->logger = app(LoggerInterface::class);
        }
    }

    private function determineRequestClassName(Request $request, string $action): ?string
    {
        return $this->isPivotRoute($request) ? $this->buildPivotRequestClassName($action, $request) : $this->buildModuleRequestClassName($action, $request);
    }

    private function buildPivotRequestClassName(string $action, Request $request): ?string
    {
        $pivotContext = $this->extractPivotContext($request);

        if (! $this->isValidPivotContext($pivotContext)) {
            $this->logger->warning('Invalid pivot context for request resolution', [
                'action' => $action,
                'context' => $pivotContext,
            ]);

            return null;
        }

        return $this->constructPivotClassName($pivotContext, $action);
    }

    private function buildModuleRequestClassName(string $action, Request $request): ?string
    {
        $modelPath = $this->resolveModelPath($request);

        if (empty($modelPath)) {
            $this->logger->warning('Could not resolve model path for request', [
                'action' => $action,
            ]);

            return null;
        }

        return $this->constructModuleClassName($modelPath, $action);
    }

    private function extractPivotContext(Request $request): array
    {
        return [
            'mainModelPath' => $request->attributes->get('mainModelPath'),
            'relationName' => $request->attributes->get('relationName'),
            'tableName' => $request->attributes->get('tableName'),
        ];
    }

    private function isValidPivotContext(array $context): bool
    {
        $requiredKeys = ['mainModelPath', 'relationName', 'tableName'];

        foreach ($requiredKeys as $key) {
            if (empty($context[$key]) || ! is_string($context[$key])) {
                return false;
            }
        }

        return true;
    }

    private function constructPivotClassName(array $pivotContext, string $action): string
    {
        $namespaceSegments = $this->buildNamespaceSegments($pivotContext['mainModelPath']);
        $namespace = $this->buildPivotNamespace($namespaceSegments, $pivotContext);
        $className = $this->buildPivotClassName($pivotContext, $action);

        return $namespace.'\\'.$className;
    }

    private function constructModuleClassName(string $modelPath, string $action): string
    {
        $pathSegments = $this->splitPath($modelPath);
        $namespaceSegments = $this->buildNamespaceSegments($modelPath);
        $namespace = self::REQUEST_NAMESPACE_BASE.implode('\\', $namespaceSegments);
        $className = Str::studly(end($pathSegments)).Str::studly($action).self::REQUEST_CLASS_SUFFIX;

        return $namespace.'\\'.$className;
    }

    private function buildNamespaceSegments(string $modelPath): array
    {
        $pathSegments = $this->splitPath($modelPath);

        return array_map(static fn (string $segment): string => Str::studly($segment), $pathSegments);
    }

    private function splitPath(string $path): array
    {
        return array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== '');
    }

    private function buildPivotNamespace(array $namespaceSegments, array $pivotContext): string
    {
        return self::REQUEST_NAMESPACE_BASE.implode('\\', $namespaceSegments).self::PIVOT_NAMESPACE_SUFFIX.Str::studly($pivotContext['tableName']).Str::studly($pivotContext['relationName']);
    }

    private function buildPivotClassName(array $pivotContext, string $action): string
    {
        return Str::studly($pivotContext['tableName']).Str::studly($pivotContext['relationName']).Str::studly($action).self::REQUEST_CLASS_SUFFIX;
    }

    private function resolveModelPath(Request $request): ?string
    {
        $modelPath = $request->attributes->get('mainModelPath');

        if (! empty($modelPath) && is_string($modelPath)) {
            return $modelPath;
        }

        $currentRoute = $this->getCurrentRoute();
        $routeParameter = $currentRoute?->parameter(self::ROUTE_PATH_PARAMETER);

        return is_string($routeParameter) ? $routeParameter : null;
    }

    private function getCurrentAction(): string
    {
        $currentRoute = $this->getCurrentRoute();

        if (! $currentRoute instanceof Route) {
            return self::UNKNOWN_ACTION;
        }

        $routeName = $currentRoute->getName();

        if ($this->isPivotRouteName($routeName)) {
            return $this->extractPivotAction($routeName);
        }

        $routeAction = $currentRoute->getActionName();

        return $this->extractActionFromRouteName($routeAction);
    }

    private function isPivotRoute(Request $request): bool
    {
        return $request->attributes->getBoolean('isPivotRoute');
    }

    private function isPivotRouteName(?string $routeName): bool
    {
        return is_string($routeName) && str_contains($routeName, self::PIVOT_ROUTE_PREFIX);
    }

    private function extractPivotAction(string $routeName): string
    {
        $actionWithPrefix = Str::afterLast($routeName, '.');

        return str_replace(self::PIVOT_ROUTE_PREFIX, '', $actionWithPrefix) ?: self::UNKNOWN_ACTION;
    }

    private function extractActionFromRouteName(?string $routeAction): string
    {
        if (! is_string($routeAction) || empty($routeAction)) {
            return self::UNKNOWN_ACTION;
        }

        $action = Str::afterLast($routeAction, '@');

        return ! empty($action) ? $action : self::UNKNOWN_ACTION;
    }

    private function isValidRequestClass(?string $className): bool
    {
        return is_string($className) && ! empty($className) && class_exists($className);
    }

    private function instantiateRequestClass(string $className): object
    {
        return $this->container->make($className);
    }

    private function resolveFallbackRequest(mixed $fallbackRequest, string $currentAction): object
    {
        if (is_object($fallbackRequest)) {
            return $fallbackRequest;
        }

        if (is_string($fallbackRequest) && class_exists($fallbackRequest)) {
            return $this->container->make($fallbackRequest);
        }

        throw new InvalidArgumentException(
            sprintf('No valid request class could be resolved for action: %s', $currentAction),
        );
    }

    private function getCurrentRequest(): Request
    {
        return app(Request::class);
    }

    private function getCurrentRoute(): ?Route
    {
        if ($this->cachedRoute === null) {
            $this->cachedRoute = RouteFacade::current();
        }

        return $this->cachedRoute;
    }

    private function logRequestResolution(string $action): void
    {
        $this->logger->debug('Attempting to resolve form request', [
            'action' => $action,
            'route_name' => $this->getCurrentRoute()?->getName(),
        ]);
    }

    private function handleResolutionError(Throwable $exception, string $action): void
    {
        $this->logger->error('Form request resolution failed', [
            'action' => $action,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    protected function resetFormRequestCache(): void
    {
        $this->cachedRoute = null;
    }
}
