<?php

namespace Io\Discord;

use Closure;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Tenma\Discord\{Bot, Message, Option};
use Tenma\Discord\Events\Event;
use Tenma\Discord\Interactions\{Autocomplete, Click, Command, Submit};
use Io\Container;
use Io\Discord\Attributes\{ButtonClick, Describe, ModalSubmit, OnEvent, SlashCommand};

/**
 * The classes whose #[SlashCommand], #[ButtonClick], #[ModalSubmit] and #[OnEvent] methods the bot calls. Each
 * call gets its own copy of the class, made from the container's instance with its #[Inject] services, as a
 * controller does per request. Another plugin adds its classes in boot(Handlers $handlers).
 */
final class Handlers
{
    public function __construct(
        private readonly Bot $bot,
        private readonly Container $container,
    ) {}

    /** @param class-string ...$classes */
    public function add(string ...$classes): self
    {
        foreach ($classes as $class) {
            foreach (new ReflectionClass($class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $this->register($method);
            }
        }

        return $this;
    }

    private function register(ReflectionMethod $method): void
    {
        foreach ($method->getAttributes(SlashCommand::class) as $attribute) {
            $this->command($method, $attribute->newInstance());
        }

        foreach ($method->getAttributes(ButtonClick::class) as $attribute) {
            $handler = fn (Click $click) => self::reply($this->call($method, $click));
            $this->bot->button($attribute->newInstance()->id, $handler);
        }

        foreach ($method->getAttributes(ModalSubmit::class) as $attribute) {
            $handler = function (Submit $submit) use ($method) {
                $field = fn (ReflectionParameter $parameter) => $submit->value($parameter->getName());

                return self::reply($this->call($method, $submit, $field));
            };
            $this->bot->modal($attribute->newInstance()->id, $handler);
        }

        if ($method->getAttributes(OnEvent::class) !== []) {
            $this->bot->on(self::eventOf($method), fn (Event $event) => $this->call($method, $event));
        }
    }

    /** Registers a slash command whose options are the method's string, int, float and bool parameters. */
    private function command(ReflectionMethod $method, SlashCommand $command): void
    {
        $options = [];
        foreach ($method->getParameters() as $parameter) {
            $option = $this->option($method, $parameter);
            if ($option !== null) {
                $options[] = $option;
            }
        }

        // Discord wants the required options first
        usort($options, fn (Option $a, Option $b) => $b->required <=> $a->required);

        $handler = function (Command $interaction) use ($method) {
            $option = fn (ReflectionParameter $parameter) => $interaction->option(self::optionName($parameter));

            return self::reply($this->call($method, $interaction, $option));
        };
        $name = $command->name ?? strtolower($method->getName());
        $this->bot->command($name, $command->description, $handler, $options);
    }

    private function option(ReflectionMethod $method, ReflectionParameter $parameter): ?Option
    {
        $type = $parameter->getType();
        $type = $type instanceof ReflectionNamedType ? $type->getName() : '';
        if (!in_array($type, ['string', 'int', 'float', 'bool'], true)) {
            return null;
        }

        $name = self::optionName($parameter);
        $describe = ($parameter->getAttributes(Describe::class)[0] ?? null)?->newInstance();
        $description = $describe->description ?? $name;
        $required = !$parameter->allowsNull() && !$parameter->isDefaultValueAvailable();
        $suggest = $describe?->suggest === null ? null : $this->suggester($method, $describe->suggest);

        return match ($type) {
            'string' => Option::string($name, $description, $required, $suggest),
            'int' => Option::integer($name, $description, $required, $suggest),
            'float' => Option::number($name, $description, $required, $suggest),
            default => Option::boolean($name, $description, $required),
        };
    }

    /** @return Closure(Autocomplete): array<string|int|float> */
    private function suggester(ReflectionMethod $command, string $name): Closure
    {
        if (!method_exists($command->class, $name)) {
            throw new LogicException("#[Describe(suggest: '$name')] on {$command->class}::{$command->name}: no such method");
        }
        $method = new ReflectionMethod($command->class, $name);

        return function (Autocomplete $typed) use ($method): array {
            $returned = $this->call($method, $typed);

            $suggestions = [];
            foreach (is_array($returned) ? $returned : [] as $name => $value) {
                if (is_string($value) || is_int($value) || is_float($value)) {
                    $suggestions[$name] = $value;
                }
            }

            return $suggestions;
        };
    }

    /**
     * Calls the method on a copy of its class's service: a parameter whose type the subject has gets the subject, a
     * Bot the bot, any other the value $value finds for it, else its default.
     * @param (Closure(ReflectionParameter): mixed)|null $value
     */
    private function call(ReflectionMethod $method, object $subject, ?Closure $value = null): mixed
    {
        $arguments = [];
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            $type = $type instanceof ReflectionNamedType ? $type->getName() : '';

            $arguments[] = match (true) {
                $subject instanceof $type => $subject,
                $type === Bot::class => $this->bot,
                default => ($value === null ? null : $value($parameter)) ?? self::fallback($parameter),
            };
        }

        $handler = clone $this->container->get($method->class);

        return $method->invokeArgs($handler, $arguments);
    }

    private static function fallback(ReflectionParameter $parameter): mixed
    {
        return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
    }

    /** What a handler returned, if it is a reply; anything else answers nothing. */
    private static function reply(mixed $result): string|Message|null
    {
        return is_string($result) || $result instanceof Message ? $result : null;
    }

    /** `$stationUrl` becomes `station_url`: Discord takes option names in lower case only. */
    private static function optionName(ReflectionParameter $parameter): string
    {
        return strtolower((string) preg_replace('/(?<=[a-z0-9])([A-Z])/', '_$1', $parameter->getName()));
    }

    /** @return class-string<Event> */
    private static function eventOf(ReflectionMethod $method): string
    {
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && is_a($type->getName(), Event::class, true)) {
                return $type->getName();
            }
        }

        throw new LogicException("#[OnEvent] {$method->class}::{$method->name} needs a parameter of an event's type");
    }
}
