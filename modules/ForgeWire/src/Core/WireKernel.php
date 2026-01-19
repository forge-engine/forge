<?php

namespace App\Modules\ForgeWire\Core;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Support\Checksum;
use App\Modules\ForgeWire\Support\ForgeWireResponse;
use Forge\Core\DI\Container;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Exceptions\ValidationException;
use Forge\Core\Session\SessionInterface;
use Forge\Core\Validation\Validator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use ReflectionNamedType;
use LogicException;

final class WireKernel
{
  private static array $reflCache = [];
  private static array $actionCache = [];
  private static array $sharedStateComponents = [];

  public function __construct(
    private Container $container,
    private Hydrator $hydrator,
    private Checksum $checksum,
  ) {
  }

  public function process(array $p, Request $request, SessionInterface $session): array
  {
    $id = (string) ($p["id"] ?? "");
    $class = (string) ($p["controller"] ?? $session->get("forgewire:{$id}:class") ?? "");
    $action = $p["action"] ?? null;
    $args = is_array($p["args"] ?? []) ? $p["args"] : [];
    $dirty = (array) ($p["dirty"] ?? []);
    $depends = is_array($p["depends"] ?? null) ? $p["depends"] : null;

    $sessionKey = "forgewire:{$id}";
    $sharedKey = "forgewire:shared:{$class}";
    $ctx = [
      "class" => $class,
      "path" => (string) ($p["fingerprint"]["path"] ?? "/"),
    ];

    if ($class === "" || !class_exists($class)) {
      return ["ignored" => true, "id" => $id];
    }

    if (!isset(self::$reflCache[$class])) {
      $refl = new ReflectionClass($class);
      self::$reflCache[$class] = !empty($refl->getAttributes(Reactive::class));
    }

    if (!self::$reflCache[$class]) {
      return ["ignored" => true, "id" => $id];
    }

    if ($depends !== null) {
      $session->set("forgewire:{$id}:uses", $depends);
    }

    $state = $session->get($sessionKey, []);
    $hasState = !empty($state);

    if ($action !== null) {
      $ctx["action"] = $action;
      $ctx["args"] = $args;
    }

    $requestKey = $this->getRequestKey($id, $action, $args, $p["checksum"] ?? '');
    $processingKey = "forgewire:processing:{$requestKey}";
    $processedKey = "forgewire:processed:{$requestKey}";

    if ($session->has($processingKey)) {
      $processingTime = $session->get($processingKey);
      if (time() - $processingTime < 5) {
        return ["ignored" => true, "id" => $id];
      }
    }

    if ($session->has($processedKey)) {
      $processedTime = $session->get($processedKey);
      if (time() - $processedTime < 2) {
        // TODO: Analyze this behaviour in production to check if we add it back or not
        //return ["ignored" => true, "id" => $id];
      }
    }

    $session->set($processingKey, time());

    try {
      $this->checksum->verify(
        $p["checksum"] ?? null,
        $sessionKey,
        $session,
        $ctx,
      );

      $instance = $this->container->make($class);

      try {
        $reflection = new ReflectionClass($instance);
        if ($reflection->hasProperty('__fw_id')) {
          $prop = $reflection->getProperty('__fw_id');
          $prop->setAccessible(true);
          $prop->setValue($instance, $id);
        }
      } catch (\ReflectionException $e) {
      }

      $isSubmit =
        $action !== null
        && $action !== 'input'
        && $this->isSubmitAction($class, $action);

      if ($session->has($processedKey)) {
        $processedTime = $session->get($processedKey);
        if (time() - $processedTime < 2) {
          if ($action === 'input' || $isSubmit) {
            return ["ignored" => true, "id" => $id];
          }
        }
      }

      if (!$isSubmit) {
        $dirty = $this->filterDirty($dirty, $session, $sessionKey, $class);
      }

      $shouldValidateState =
        $action === 'input'
        || $isSubmit;

      if ($shouldValidateState) {
        $errors = $this->validateReactiveState(
          $instance,
          $dirty,
          $class,
          $isSubmit,
          $id,
          $session
        );

        if ($errors !== []) {
          $stateCtx = $ctx;
          unset($stateCtx['action'], $stateCtx['args']);
          return [
            "html" => "",
            "state" => null,
            "checksum" => $this->checksum->sign($sessionKey, $session, $stateCtx),
            "events" => [],
            "redirect" => null,
            "flash" => [],
            "errors" => $errors,
          ];
        }
      }

      $sharedBag = $session->get($sharedKey, []);
      $sharedStatesBefore = $this->getSharedStatesFromSession($sharedBag, $class);

      $this->hydrator->hydrate($instance, $dirty, $session, $sessionKey, $sharedKey);

      $responseContext = new ForgeWireResponse();
      ForgeWireResponse::setContext($id, $responseContext);

      $html = "";

      if ($action === "input" && !method_exists($instance, "input")) {
        $action = $session->get("forgewire:{$id}:action") ?? "index";
      }

      if ($action) {
        $html = $this->callAction($instance, $action, $request, $session, $args, $dirty, true, $id);
      }

      if ($html === "") {
        $renderAction = $session->get("forgewire:{$id}:action") ?? "index";
        if (method_exists($instance, $renderAction)) {
          $html = $this->callAction($instance, $renderAction, $request, $session, $args, $dirty, false, $id);
        }
      }

      if ($html === "" && method_exists($instance, 'render')) {
        $html = (string) $instance->render();
      }

      $redirect = $responseContext->getRedirect();
      $flashes = $responseContext->getFlashes();
      $events = $responseContext->getEvents();
      ForgeWireResponse::clearContext($id);

      $this->parseSharedGroupsFromHtml($html, $session, $class);
      $this->discoverSharedGroupFromRegisteredComponents($session, $class);
      $this->initializeSharedGroupIfNeeded($id, $class, $session, $request, $sharedKey, $html);
      $this->parseAndStoreUsesForAllComponents($html, $session, $class);
      $this->discoverAndStoreUsesForRegisteredComponents($html, $session, $class);
      $this->assertDependenciesRegisteredForController($session, $class);

      $this->trackComponentsInHtml($html, $session);

      $componentHtml = $this->extractComponentHtml($html, $id);
      if ($componentHtml === null) {
        $componentHtml = $html;
      }

      $this->storeExpectedActions($componentHtml, $id, $session, $sessionKey);

      $state = $this->hydrator->dehydrate($instance, $session, $sessionKey, $sharedKey);

      $stateCtx = $ctx;
      unset($stateCtx['action'], $stateCtx['args']);
      $sig = $this->checksum->sign($sessionKey, $session, $stateCtx);

      $sharedStatesAfter = $this->getSharedStates($instance, $class);
      $sharedStateChanges = $this->getSharedStateChanges($sharedStatesBefore, $sharedStatesAfter);

      $affectedComponents = [];
      $updates = [];
      if (!empty($sharedStateChanges)) {
        $affectedComponents = $this->findAffectedComponents($sharedStateChanges, $session, $class, $id);

        foreach ($affectedComponents as $component) {
          if ($component['id'] === $id) {
            continue;
          }

          $update = $this->renderAffectedComponent(
            $component['id'],
            $component['class'],
            $request,
            $session,
            $sharedKey
          );

          if ($update !== null) {
            $updates[] = $update;
          }
        }
      }

      $eventData = [];
      foreach ($events as $event) {
        $eventData[] = [
          'name' => $event['name'],
          'data' => $event['data'],
        ];
      }

      $result = [
        "html" => $componentHtml,
        "state" => $state,
        "checksum" => $sig,
        "events" => $eventData,
        "redirect" => $redirect,
        "flash" => $flashes,
        "updates" => $updates,
      ];

      $session->set($processedKey, time());
      $session->remove($processingKey);

      return $result;
    } catch (\RuntimeException $e) {
      if (str_contains($e->getMessage(), 'checksum mismatch') || str_contains($e->getMessage(), 'Fingerprint mismatch')) {
        if ($session->has($processedKey)) {
          $processedTime = $session->get($processedKey);
          if (time() - $processedTime < 2) {
            $session->remove($processingKey);
            return ["ignored" => true, "id" => $id];
          }
        }
      }
      $session->remove($processingKey);
      throw $e;
    }
  }

  private function getRequestKey(string $id, ?string $action, array $args, string $checksum): string
  {
    $argsJson = json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return md5("{$id}:{$action}:{$argsJson}:{$checksum}");
  }

  private function callAction($instance, string $action, Request $request, SessionInterface $session, array $args, array $dirty, bool $isExplicitAction, string $id): string
  {
    $class = $instance::class;
    $cacheKey = "{$class}::{$action}";

    if (!isset(self::$actionCache[$cacheKey])) {
      if (!method_exists($instance, $action)) {
        self::$actionCache[$cacheKey] = false;
        return "";
      }

      $rm = new ReflectionMethod($instance, $action);
      if (!$rm->isPublic()) {
        throw new RuntimeException("Action method must be public: {$action}");
      }

      $isAction = !empty($rm->getAttributes(Action::class));
      $params = [];
      foreach ($rm->getParameters() as $param) {
        $typeName = null;
        if ($param->hasType()) {
          $type = $param->getType();
          if ($type instanceof ReflectionNamedType) {
            $typeName = ltrim($type->getName(), '\\');
          }
        }
        $params[] = [
          'name' => $param->getName(),
          'type' => $typeName,
        ];
      }

      self::$actionCache[$cacheKey] = [
        'rm' => $rm,
        'isAction' => $isAction,
        'params' => $params,
      ];
    }

    $meta = self::$actionCache[$cacheKey];
    if ($meta === false) {
      return "";
    }

    /** @var ReflectionMethod $rm */
    $rm = $meta['rm'];

    if ($isExplicitAction) {
      $originalAction = $session->get("forgewire:{$id}:action") ?? "index";
      if ($action !== $originalAction && !$meta['isAction']) {
        throw new RuntimeException("Action not allowed: {$action}. Must be marked with #[Action].");
      }
    }

    $methodArgs = [];
    foreach ($meta['params'] as $i => $pMeta) {
      $name = $pMeta['name'];
      $typeName = $pMeta['type'];
      $v = null;

      if ($typeName !== null) {
        if ($typeName === ltrim(Request::class, '\\'))
          $v = $request;
        elseif ($typeName === ltrim(SessionInterface::class, '\\'))
          $v = $session;
      }

      if ($v === null) {
        if (is_array($args)) {
          $v = $args[$name] ?? $args[$i] ?? $dirty[$name] ?? null;

          if ($v === null) {
            $v = $this->findCaseInsensitiveParam($args, $name) ?? $dirty[$name] ?? null;
          }
        } else {
          $v = $dirty[$name] ?? null;
        }

        if ($typeName !== null && $v !== null) {
          if ($typeName === "int" && is_string($v))
            $v = (int) $v;
          elseif ($typeName === "float" && is_string($v))
            $v = (float) $v;
          elseif ($typeName === "bool" && is_string($v))
            $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
          elseif ($typeName === "string" && !is_string($v))
            $v = (string) $v;
        }
      }
      $methodArgs[] = $v;
    }

    $res = $rm->invokeArgs($instance, $methodArgs);
    if ($res instanceof Response) {
      return $res->getContent();
    }
    return (string) $res;
  }

  private function findCaseInsensitiveParam(array $args, string $name): mixed
  {
    foreach ($args as $key => $value) {
      if (is_string($key) && strcasecmp($key, $name) === 0) {
        return $value;
      }
    }
    return null;
  }

  private function extractActionsFromHtml(string $html, string $componentId): array
  {
    $actions = [];

    if (empty($html)) {
      return $actions;
    }

    $pattern = '/<[^>]*fw:click=["\']([^"\']+)["\'][^>]*>/i';
    if (!preg_match_all($pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
      return $actions;
    }

    foreach ($matches as $match) {
      $fullTag = $match[0][0];
      $actionName = trim($match[1][0] ?? '');

      if (empty($actionName)) {
        continue;
      }

      $args = [];

      if (preg_match_all('/fw:param-([a-zA-Z0-9_-]+)=["\']([^"\']*)["\']/i', $fullTag, $paramMatches, PREG_SET_ORDER)) {
        foreach ($paramMatches as $paramMatch) {
          $paramName = strtolower(trim($paramMatch[1]));
          $paramValue = $paramMatch[2];
          $args[$paramName] = $paramValue;
        }
      }

      $actions[] = [
        'action' => $actionName,
        'args' => $args,
      ];
    }

    return $actions;
  }

  private function storeExpectedActions(string $html, string $componentId, SessionInterface $session, string $sessionKey): void
  {
    $actions = $this->extractActionsFromHtml($html, $componentId);

    $prefix = $sessionKey . ':actions:';
    $allSession = $session->all();
    foreach ($allSession as $key => $value) {
      if (str_starts_with($key, $prefix)) {
        $session->remove($key);
      }
    }

    foreach ($actions as $actionData) {
      $this->checksum->storeExpectedAction($sessionKey, $session, $actionData['action'], $actionData['args']);
    }
  }

  private function hasAnyExpectedActions(string $sessionKey, SessionInterface $session): bool
  {
    $prefix = $sessionKey . ':actions:';
    $allSession = $session->all();
    foreach ($allSession as $key => $value) {
      if (str_starts_with($key, $prefix)) {
        return true;
      }
    }
    return false;
  }

  private function isSubmitAction(string $class, string $action): bool
  {
    $rm = new ReflectionMethod($class, $action);

    foreach ($rm->getAttributes(Action::class) as $attr) {
      $instance = $attr->newInstance();
      return $instance->submit ?? false;
    }

    return false;
  }

  private function getSharedStates(object $instance, string $class): array
  {
    $recipe = Hydrator::getRecipe($class);
    $sharedStates = [];

    foreach ($recipe as $propName => $cfg) {
      if (($cfg['kind'] ?? null) === 'state' && ($cfg['shared'] ?? false)) {
        $sharedStates[$propName] = $cfg['reader']($instance);
      }
    }

    return $sharedStates;
  }

  private function getSharedStatesFromSession(array $sharedBag, string $class): array
  {
    $recipe = Hydrator::getRecipe($class);
    $sharedStates = [];

    foreach ($recipe as $propName => $cfg) {
      if (($cfg['kind'] ?? null) === 'state' && ($cfg['shared'] ?? false)) {
        $sharedStates[$propName] = $sharedBag[$propName] ?? null;
      }
    }

    return $sharedStates;
  }

  private function getSharedStateChanges(array $before, array $after): array
  {
    $changes = [];
    $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));

    foreach ($allKeys as $propName) {
      $beforeValue = $before[$propName] ?? null;
      $afterValue = $after[$propName] ?? null;

      $hasChanged = false;

      if (!array_key_exists($propName, $before) && array_key_exists($propName, $after)) {
        $hasChanged = true;
      } elseif (array_key_exists($propName, $before) && !array_key_exists($propName, $after)) {
        $hasChanged = true;
      } else {
        if (is_array($afterValue) || is_array($beforeValue)) {
          $hasChanged = json_encode($beforeValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !== json_encode($afterValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
          $hasChanged = $beforeValue !== $afterValue;
        }
      }

      if ($hasChanged) {
        $changes[$propName] = $afterValue;
      }
    }

    return $changes;
  }

  private function findAffectedComponents(
    array $sharedStateChanges,
    SessionInterface $session,
    string $controllerClass,
    string $triggeringId
  ): array {
    $affected = [];
    $changedKeys = array_keys($sharedStateChanges);

    foreach ($session->all() as $sessionKey => $_) {
      if (!preg_match('/^forgewire:(.+):uses$/', $sessionKey, $m)) {
        continue;
      }

      $componentId = $m[1];

      if ($componentId === $triggeringId) {
        continue;
      }

      $componentClass = $session->get("forgewire:{$componentId}:class");
      if ($componentClass !== $controllerClass) {
        continue;
      }

      $uses = $session->get($sessionKey, []);
      if (!is_array($uses)) {
        continue;
      }

      if (!array_intersect($uses, $changedKeys)) {
        continue;
      }

      $affected[] = [
        'id' => $componentId,
        'class' => $componentClass,
      ];
    }

    return $affected;
  }

  private function renderAffectedComponent(
    string $componentId,
    string $controllerClass,
    Request $request,
    SessionInterface $session,
    string $sharedKey
  ): ?array {
    $sessionKey = "forgewire:{$componentId}";

    $fp = (array) $session->get($sessionKey . ':fp', []);
    $storedPath = (string) ($fp['path'] ?? $request->getPath());

    $ctx = [
      "class" => $controllerClass,
      "path" => $storedPath,
    ];

    if (!$session->has($sessionKey) && !$session->has("forgewire:{$componentId}:class")) {
      return null;
    }

    $instance = $this->container->make($controllerClass);
    $this->hydrator->hydrate($instance, [], $session, $sessionKey, $sharedKey);

    $action = $session->get("forgewire:{$componentId}:action") ?? "index";

    $html = "";
    if (method_exists($instance, $action)) {
      $html = $this->callAction($instance, $action, $request, $session, [], [], false, $componentId);
    }

    if ($html === "" && method_exists($instance, 'render')) {
      $html = (string) $instance->render();
    }

    if ($html === "") {
      return null;
    }

    $componentHtml = $this->extractComponentHtml($html, $componentId);

    if ($componentHtml === null) {
      return null;
    }

    $this->parseAndStoreUses($componentHtml, $componentId, $session);
    $this->storeExpectedActions($componentHtml, $componentId, $session, $sessionKey);

    $targetElements = $this->extractTargetElements($componentHtml);

    if (!empty($targetElements)) {
      $componentHtml = '<div>' . implode('', $targetElements) . '</div>';
    }

    $state = $this->hydrator->dehydrate($instance, $session, $sessionKey, $sharedKey);
    $checksum = $this->checksum->sign($sessionKey, $session, $ctx);

    return [
      "id" => $componentId,
      "html" => $componentHtml,
      "state" => $state,
      "checksum" => $checksum,
    ];
  }

  private function validateReactiveState(
    object $instance,
    array $dirty,
    string $class,
    bool $isSubmit,
    string $id,
    SessionInterface $session
  ): array {
    $recipe = Hydrator::getRecipe($class);

    $data = [];
    $rules = [];
    $messages = [];

    foreach ($recipe as $prop => $cfg) {
      if (
        ($cfg['kind'] ?? null) !== 'state'
        || !isset($cfg['validate'])
      ) {
        continue;
      }

      if (!array_key_exists($prop, $dirty)) {
        continue;
      }

      if (!$cfg['public']) {
        continue;
      }

      $value = $dirty[$prop];

      $data[$prop] = $value;
      $rules[$prop] = $cfg['validate']['rules'];

      if (!empty($cfg['validate']['messages'])) {
        $messages[$prop] = $cfg['validate']['messages'];
      }
    }

    if ($data === []) {
      return [];
    }

    $flatMessages = [];

    foreach ($messages as $field => $fieldMessages) {
      foreach ($fieldMessages as $rule => $message) {
        $flatMessages["{$field}.{$rule}"] = $message;
      }
    }

    try {
      (new Validator(
        data: $data,
        rules: $rules,
        messages: $flatMessages,
        onlyPresent: !$isSubmit
      ))->validate();

      return [];
    } catch (ValidationException $e) {
      return $e->errors();
    }
  }

  private function filterDirty(
    array $dirty,
    SessionInterface $session,
    string $sessionKey,
    string $class
  ): array {
    $stateBag = $session->get($sessionKey, []);
    $filtered = [];

    $recipe = Hydrator::getRecipe($class);

    foreach ($dirty as $key => $value) {
      if (isset($recipe[$key]) && !$recipe[$key]['public']) {
        continue;
      }

      if (!array_key_exists($key, $stateBag)) {
        $filtered[$key] = $value;
        continue;
      }

      if ($stateBag[$key] !== $value) {
        $filtered[$key] = $value;
      }
    }

    return $filtered;
  }

  private function actionTouchesValidatedState(
    string $class,
    ?string $action,
    array $dirty
  ): bool {
    if ($action === null) {
      return false;
    }

    $recipe = Hydrator::getRecipe($class);

    foreach ($dirty as $prop => $_) {
      if (
        isset($recipe[$prop]) &&
        ($recipe[$prop]['kind'] ?? null) === 'state' &&
        isset($recipe[$prop]['validate'])
      ) {
        return true;
      }
    }

    return false;
  }

  private function parseAndStoreUses(string $html, string $componentId, SessionInterface $session): void
  {
    $uses = [];

    if (preg_match_all('/fw:uses=["\']([^"\']+)["\']/', $html, $matches)) {
      foreach ($matches[1] as $match) {
        $values = array_map('trim', explode(',', $match));
        foreach ($values as $value) {
          if ($value !== '') {
            $uses[$value] = true;
          }
        }
      }
    }

    if (preg_match_all('/fw:depends=["\']([^"\']+)["\']/', $html, $matches)) {
      foreach ($matches[1] as $match) {
        $values = array_map('trim', explode(',', $match));
        foreach ($values as $value) {
          if ($value !== '') {
            $uses[$value] = true;
          }
        }
      }
    }

    $session->set("forgewire:{$componentId}:uses", array_keys($uses));
  }

  private function parseAndStoreUsesForAllComponents(string $html, SessionInterface $session, ?string $controllerClass = null): void
  {
    if (preg_match_all('/fw:id=["\']([^"\']+)["\']/', $html, $idMatches)) {
      foreach ($idMatches[1] as $componentId) {
        $componentClass = $session->get("forgewire:{$componentId}:class");

        if ($componentClass === null) {
          if ($controllerClass !== null) {
            $session->set("forgewire:{$componentId}:class", $controllerClass);
            $session->set("forgewire:{$componentId}:action", "index");
            $componentClass = $controllerClass;
          } else {
            continue;
          }
        }

        if ($controllerClass !== null && $componentClass !== $controllerClass) {
          continue;
        }

        $componentHtml = $this->extractComponentHtml($html, $componentId);
        if ($componentHtml !== null) {
          $this->parseAndStoreUses($componentHtml, $componentId, $session);
        } else {
          $this->parseAndStoreUses($html, $componentId, $session);
        }
      }
    }
  }

  private function discoverAndStoreUsesForRegisteredComponents(string $html, SessionInterface $session, string $controllerClass): void
  {
    $allSessionKeys = array_keys($session->all());
    $foundInHtml = [];


    if (preg_match_all('/fw:id=["\']([^"\']+)["\']/', $html, $idMatches)) {
      $foundInHtml = array_flip($idMatches[1]);
    }

    foreach ($allSessionKeys as $sessionKey) {
      if (!str_starts_with($sessionKey, 'forgewire:')) {
        continue;
      }

      if (str_contains($sessionKey, ':shared:') || str_contains($sessionKey, ':class') || str_contains($sessionKey, ':action') || str_contains($sessionKey, ':fp') || str_contains($sessionKey, ':sig') || str_contains($sessionKey, ':uses')) {
        continue;
      }

      if (!preg_match('/^forgewire:(.+)$/', $sessionKey, $matches)) {
        continue;
      }

      $componentId = $matches[1];
      $componentClass = $session->get("forgewire:{$componentId}:class");

      if ($componentClass !== $controllerClass) {
        continue;
      }

      if (isset($foundInHtml[$componentId])) {
        continue;
      }

      if ($session->has("forgewire:{$componentId}:uses")) {
        continue;
      }

      $componentHtml = $this->extractComponentHtml($html, $componentId);
      if ($componentHtml !== null) {
        $this->parseAndStoreUses($componentHtml, $componentId, $session);
      } else {
        $this->parseAndStoreUses($html, $componentId, $session);
      }
    }
  }

  private function assertDependenciesRegisteredForController(SessionInterface $session, string $controllerClass): void
  {
    $allSessionKeys = array_keys($session->all());
    $componentIds = [];

    foreach ($allSessionKeys as $sessionKey) {
      if (!preg_match('/^forgewire:([^:]+):class$/', $sessionKey, $matches)) {
        continue;
      }

      $componentId = $matches[1];
      $componentClass = $session->get($sessionKey);

      if ($componentClass !== $controllerClass) {
        continue;
      }

      $componentIds[] = $componentId;
    }

    if (empty($componentIds)) {
      return;
    }

    foreach ($componentIds as $componentId) {
      if (!$session->has("forgewire:{$componentId}:uses")) {
        throw new LogicException(
          "Reactive component {$componentId} is active but has no dependencies registered"
        );
      }
    }
  }

  private function extractComponentHtml(string $fullHtml, string $componentId): ?string
  {
    $escapedId = preg_quote($componentId, '/');

    $pattern = '/<([^\s>]+)[^>]*fw:id=["\']' . $escapedId . '["\'][^>]*(?:\/>|>)/i';

    if (!preg_match($pattern, $fullHtml, $tagMatch, PREG_OFFSET_CAPTURE)) {
      return null;
    }

    $rootTagName = strtolower($tagMatch[1][0]);
    $startPos = $tagMatch[0][1];
    $tagContent = $tagMatch[0][0];

    if (substr(trim($tagContent), -2) === '/>') {
      return $tagContent;
    }

    $stack = [$rootTagName];
    $pos = $startPos + strlen($tagContent);
    $len = strlen($fullHtml);
    $result = $tagContent;

    $selfClosingTags = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    while ($pos < $len && !empty($stack)) {
      $nextTag = strpos($fullHtml, '<', $pos);
      if ($nextTag === false) {
        $result .= substr($fullHtml, $pos);
        break;
      }

      $result .= substr($fullHtml, $pos, $nextTag - $pos);
      $pos = $nextTag;

      if ($pos + 3 < $len && substr($fullHtml, $pos, 4) === '<!--') {
        $commentEnd = strpos($fullHtml, '-->', $pos);
        if ($commentEnd !== false) {
          $result .= substr($fullHtml, $pos, $commentEnd - $pos + 3);
          $pos = $commentEnd + 3;
          continue;
        }
      }

      if ($pos + 1 < $len && $fullHtml[$pos + 1] === '/') {
        $closeEnd = strpos($fullHtml, '>', $pos);
        if ($closeEnd === false) {
          break;
        }

        $closeTag = substr($fullHtml, $pos, $closeEnd - $pos + 1);
        if (preg_match('/<\/([^\s>]+)/i', $closeTag, $closeMatch)) {
          $closeTagName = strtolower($closeMatch[1]);
          if (!empty($stack) && $closeTagName === $stack[count($stack) - 1]) {
            array_pop($stack);
            $result .= $closeTag;
            $pos = $closeEnd + 1;
            if (empty($stack)) {
              break;
            }
          } else {
            $result .= $closeTag;
            $pos = $closeEnd + 1;
          }
        } else {
          $result .= $closeTag;
          $pos = $closeEnd + 1;
        }
      } else {
        $openEnd = strpos($fullHtml, '>', $pos);
        if ($openEnd === false) {
          break;
        }

        $openTag = substr($fullHtml, $pos, $openEnd - $pos + 1);
        $isSelfClosing = substr(trim($openTag), -2) === '/>';

        if (!$isSelfClosing && preg_match('/<([^\s>\/]+)/i', $openTag, $openMatch)) {
          $openTagName = strtolower($openMatch[1]);
          if ($openTagName !== '!--' && !in_array($openTagName, $selfClosingTags, true)) {
            $stack[] = $openTagName;
          }
        }

        $result .= $openTag;
        $pos = $openEnd + 1;
      }
    }

    if (!empty($stack)) {
      return null;
    }

    return $result;
  }

  private function extractTargetElements(string $html): array
  {
    $len = strlen($html);
    $pos = 0;
    $targets = [];

    $stack = [];
    $captureStart = null;
    $captureDepth = null;

    $selfClosing = [
      'area',
      'base',
      'br',
      'col',
      'embed',
      'hr',
      'img',
      'input',
      'link',
      'meta',
      'param',
      'source',
      'track',
      'wbr'
    ];

    while ($pos < $len) {
      $lt = strpos($html, '<', $pos);
      if ($lt === false) {
        break;
      }

      if ($captureStart !== null) {
      }

      if (substr($html, $lt, 4) === '<!--') {
        $end = strpos($html, '-->', $lt + 4);
        $pos = $end !== false ? $end + 3 : $len;
        continue;
      }

      if (isset($html[$lt + 1]) && $html[$lt + 1] === '!') {
        $end = strpos($html, '>', $lt + 2);
        $pos = $end !== false ? $end + 1 : $len;
        continue;
      }

      if (isset($html[$lt + 1]) && $html[$lt + 1] === '/') {
        if (preg_match('/<\/\s*([^\s>]+)/A', substr($html, $lt), $m)) {
          $tag = strtolower($m[1]);
          array_pop($stack);

          if ($captureStart !== null && count($stack) < $captureDepth) {
            $targets[] = substr($html, $captureStart, $lt + strlen($m[0]) + 1 - $captureStart);
            $captureStart = null;
            $captureDepth = null;
          }
        }

        $end = strpos($html, '>', $lt);
        $pos = $end !== false ? $end + 1 : $len;
        continue;
      }

      if (preg_match('/<([^\s>\/]+)/A', substr($html, $lt), $m)) {
        $tag = strtolower($m[1]);
        $end = strpos($html, '>', $lt);
        if ($end === false) {
          break;
        }

        $fullTag = substr($html, $lt, $end - $lt + 1);
        $isSelfClosing =
          substr($fullTag, -2) === '/>' ||
          in_array($tag, $selfClosing, true);

        if (
          $captureStart === null &&
          strpos($fullTag, 'fw:target') !== false
        ) {
          $captureStart = $lt;
          $captureDepth = count($stack) + 1;
        }

        if (!$isSelfClosing) {
          $stack[] = $tag;
        }

        $pos = $end + 1;
        continue;
      }

      $pos = $lt + 1;
    }

    return $targets;
  }

  private function parseSharedGroupsFromHtml(string $html, SessionInterface $session, ?string $controllerClass = null): void
  {
    if (!preg_match_all('/<([^\s>]+)[^>]*\s*fw:shared[^>]*(?:\/>|>)/i', $html, $sharedMatches, PREG_OFFSET_CAPTURE)) {
      return;
    }

    foreach ($sharedMatches[0] as $index => $match) {
      $tagName = strtolower($sharedMatches[1][$index][0]);
      $startPos = $match[1];
      $tagContent = $match[0];

      if (substr(trim($tagContent), -2) === '/>') {
        continue;
      }

      $containerHtml = $this->extractContainerHtml($html, $tagName, $startPos);
      if ($containerHtml === null) {
        continue;
      }

      if (preg_match_all('/fw:id=["\']([^"\']+)["\']/', $containerHtml, $idMatches)) {
        $componentIds = $idMatches[1];
        $groupedByClass = [];

        foreach ($componentIds as $componentId) {
          $componentClass = $session->get("forgewire:{$componentId}:class");

          if ($componentClass === null && $controllerClass !== null) {
            $session->set("forgewire:{$componentId}:class", $controllerClass);
            $session->set("forgewire:{$componentId}:action", "index");
            $componentClass = $controllerClass;
          }

          if ($componentClass !== null) {
            if ($controllerClass !== null && $componentClass !== $controllerClass) {
              continue;
            }

            if (!isset($groupedByClass[$componentClass])) {
              $groupedByClass[$componentClass] = [];
            }
            if (!in_array($componentId, $groupedByClass[$componentClass], true)) {
              $groupedByClass[$componentClass][] = $componentId;
            }
          }
        }

        foreach ($groupedByClass as $controllerClassKey => $components) {
          $groupKey = "forgewire:shared-group:{$controllerClassKey}:components";
          $existing = $session->get($groupKey, []);
          $merged = array_unique(array_merge($existing, $components));
          $session->set($groupKey, array_values($merged));
        }
      }
    }
  }

  private function discoverSharedGroupFromRegisteredComponents(SessionInterface $session, string $controllerClass): void
  {
    $groupKey = "forgewire:shared-group:{$controllerClass}:components";

    $allSessionKeys = array_keys($session->all());
    $componentIds = [];
    $foundComponentIds = [];

    foreach ($allSessionKeys as $sessionKey) {
      if (!str_starts_with($sessionKey, 'forgewire:')) {
        continue;
      }

      if (str_contains($sessionKey, ':shared:') || str_contains($sessionKey, ':action') || str_contains($sessionKey, ':fp') || str_contains($sessionKey, ':sig') || str_contains($sessionKey, ':uses')) {
        continue;
      }

      if (str_contains($sessionKey, ':class')) {
        if (preg_match('/^forgewire:(.+):class$/', $sessionKey, $matches)) {
          $componentId = $matches[1];
          $componentClass = $session->get($sessionKey);
          if ($componentClass === $controllerClass) {
            $foundComponentIds[$componentId] = true;
          }
        }
        continue;
      }

      if (!preg_match('/^forgewire:(.+)$/', $sessionKey, $matches)) {
        continue;
      }

      $componentId = $matches[1];
      $componentClass = $session->get("forgewire:{$componentId}:class");

      if ($componentClass === $controllerClass) {
        $foundComponentIds[$componentId] = true;
      }
    }

    $componentIds = array_keys($foundComponentIds);
    if (!empty($componentIds)) {
      $existing = $session->get($groupKey, []);
      $merged = array_unique(array_merge($existing, $componentIds));
      $session->set($groupKey, array_values($merged));
    }
  }

  private function extractContainerHtml(string $fullHtml, string $tagName, int $startPos): ?string
  {
    $tagPattern = '/<' . preg_quote($tagName, '/') . '[^>]*>/i';
    if (!preg_match($tagPattern, $fullHtml, $tagMatch, 0, $startPos)) {
      return null;
    }

    $tagContent = $tagMatch[0];
    $pos = $startPos + strlen($tagContent);
    $len = strlen($fullHtml);
    $stack = [$tagName];
    $result = $tagContent;

    $selfClosingTags = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    while ($pos < $len && !empty($stack)) {
      $nextTag = strpos($fullHtml, '<', $pos);
      if ($nextTag === false) {
        $result .= substr($fullHtml, $pos);
        break;
      }

      $result .= substr($fullHtml, $pos, $nextTag - $pos);
      $pos = $nextTag;

      if ($pos + 1 < $len && $fullHtml[$pos + 1] === '/') {
        $closeEnd = strpos($fullHtml, '>', $pos);
        if ($closeEnd === false) {
          break;
        }

        $closeTag = substr($fullHtml, $pos, $closeEnd - $pos + 1);
        if (preg_match('/<\/([^\s>]+)/i', $closeTag, $closeMatch)) {
          $closeTagName = strtolower($closeMatch[1]);
          if (!empty($stack) && $closeTagName === $stack[count($stack) - 1]) {
            array_pop($stack);
            $result .= $closeTag;
            $pos = $closeEnd + 1;
            if (empty($stack)) {
              break;
            }
          } else {
            $result .= $closeTag;
            $pos = $closeEnd + 1;
          }
        } else {
          $result .= $closeTag;
          $pos = $closeEnd + 1;
        }
      } else {
        $openEnd = strpos($fullHtml, '>', $pos);
        if ($openEnd === false) {
          break;
        }

        $openTag = substr($fullHtml, $pos, $openEnd - $pos + 1);
        $isSelfClosing = substr(trim($openTag), -2) === '/>';

        if (!$isSelfClosing && preg_match('/<([^\s>\/]+)/i', $openTag, $openMatch)) {
          $openTagName = strtolower($openMatch[1]);
          if (!in_array($openTagName, $selfClosingTags, true)) {
            $stack[] = $openTagName;
          }
        }

        $result .= $openTag;
        $pos = $openEnd + 1;
      }
    }

    return empty($stack) ? $result : null;
  }

  private function initializeSharedGroupIfNeeded(
    string $componentId,
    string $controllerClass,
    SessionInterface $session,
    Request $request,
    string $sharedKey,
    string $currentHtml = ""
  ): void {
    $groupKey = "forgewire:shared-group:{$controllerClass}:components";

    if (!$session->has($groupKey)) {
      return;
    }

    $componentIds = $session->get($groupKey, []);
    if (empty($componentIds)) {
      return;
    }

    $hasUninitialized = false;
    foreach ($componentIds as $id) {
      if (!$session->has("forgewire:{$id}:class")) {
        continue;
      }

      $idClass = $session->get("forgewire:{$id}:class");
      if ($idClass !== $controllerClass) {
        continue;
      }

      if (!$session->has("forgewire:{$id}:uses")) {
        $hasUninitialized = true;
        break;
      }
    }

    if (!$hasUninitialized) {
      $initializedKey = "forgewire:shared-group:{$controllerClass}:initialized";
      $session->set($initializedKey, true);
      return;
    }

    foreach ($componentIds as $id) {
      if (!$session->has("forgewire:{$id}:class")) {
        continue;
      }

      $idClass = $session->get("forgewire:{$id}:class");
      if ($idClass !== $controllerClass) {
        continue;
      }

      if ($session->has("forgewire:{$id}:uses")) {
        continue;
      }

      $componentHtml = null;
      if ($currentHtml !== "") {
        $componentHtml = $this->extractComponentHtml($currentHtml, $id);
      }

      if ($componentHtml === null) {
        $instance = $this->container->make($controllerClass);
        $sessionKey = "forgewire:{$id}";
        $this->hydrator->hydrate($instance, [], $session, $sessionKey, $sharedKey);

        $action = $session->get("forgewire:{$id}:action") ?? "index";
        $html = "";

        if (method_exists($instance, $action)) {
          $html = $this->callAction($instance, $action, $request, $session, [], [], false, $id);
        }

        if ($html === "" && method_exists($instance, 'render')) {
          $html = (string) $instance->render();
        }

        if ($html !== "") {
          $componentHtml = $this->extractComponentHtml($html, $id);
          if ($componentHtml === null) {
            $componentHtml = $html;
          }
        }
      }

      if ($componentHtml !== null) {
        $this->parseAndStoreUses($componentHtml, $id, $session);
      }
    }

    $initializedKey = "forgewire:shared-group:{$controllerClass}:initialized";
    $session->set($initializedKey, true);
  }

  /**
   * Track components found in HTML as active
   */
  private function trackComponentsInHtml(string $html, SessionInterface $session): void
  {
    if (!preg_match_all('/fw:id=["\']([^"\']+)["\']/', $html, $matches)) {
      return;
    }

    $now = time();
    foreach ($matches[1] as $componentId) {
      $activeKey = "forgewire:active:{$componentId}";
      $session->set($activeKey, $now);
    }
  }
}
