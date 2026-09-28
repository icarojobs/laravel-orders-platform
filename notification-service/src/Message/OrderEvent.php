<?php

declare(strict_types=1);

namespace NotificationService\Message;

use DateTimeImmutable;
use Throwable;

/**
 * Mirrors App\Infrastructure\Messaging\OrderEventMessage (version 1) from the main app.
 */
final readonly class OrderEvent
{
    public const SUPPORTED_VERSION = 1;

    public function __construct(
        public string $eventId,
        public string $event,
        public DateTimeImmutable $occurredAt,
        public int $orderId,
        public string $orderNumber,
        public string $status,
        public int $totalCents,
        public int $itemsCount,
        public string $customerName,
        public string $customerEmail,
    ) {}

    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new InvalidMessage('Message body is not valid JSON.', previous: $e);
        }

        if (! is_array($data)) {
            throw new InvalidMessage('Message body must be a JSON object.');
        }

        if (($data['version'] ?? null) !== self::SUPPORTED_VERSION) {
            throw new InvalidMessage('Unsupported message version.');
        }

        $order = $data['order'] ?? null;
        $customer = is_array($order) ? ($order['customer'] ?? null) : null;

        if (! is_array($order) || ! is_array($customer)) {
            throw new InvalidMessage('Message is missing the order payload.');
        }

        $email = $customer['email'] ?? null;
        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidMessage('Customer e-mail is invalid.');
        }

        try {
            $occurredAt = new DateTimeImmutable((string) ($data['occurred_at'] ?? ''));
        } catch (Throwable $e) {
            throw new InvalidMessage('occurred_at is not a valid date.', previous: $e);
        }

        return new self(
            eventId: self::string($data, 'event_id'),
            event: self::string($data, 'event'),
            occurredAt: $occurredAt,
            orderId: self::int($order, 'id'),
            orderNumber: self::string($order, 'number'),
            status: self::string($order, 'status'),
            totalCents: self::int($order, 'total_cents'),
            itemsCount: self::int($order, 'items_count'),
            customerName: self::string($customer, 'name'),
            customerEmail: $email,
        );
    }

    /**
     * @param  array<mixed>  $data
     */
    private static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (! is_string($value) || $value === '') {
            throw new InvalidMessage("Field [{$key}] must be a non empty string.");
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $data
     */
    private static function int(array $data, string $key): int
    {
        $value = $data[$key] ?? null;
        if (! is_int($value)) {
            throw new InvalidMessage("Field [{$key}] must be an integer.");
        }

        return $value;
    }
}
