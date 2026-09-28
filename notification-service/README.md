# notification-service

Microsserviço em PHP puro que consome os eventos `order.placed` e `order.shipped`
publicados pela aplicação principal na exchange `orders.events` (RabbitMQ) e gera
as notificações para o cliente.

- Fila `notifications.order-events` com dead-letter em `notifications.order-events.dlq`.
- Idempotente: o `event_id` é único na tabela `notifications`, então reentregas não duplicam envio.
- Banco próprio (`notifications`), criado no primeiro boot.
- Mensagem inválida vai direto para a DLQ; falha inesperada é reenfileirada uma vez.

```bash
docker compose build notification-service
docker compose run --rm notification-service-test     # PHPUnit
docker compose logs -f notification-service
```
