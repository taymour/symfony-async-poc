# Async Method Execution in Symfony, this is a simple POC

## Purpose

This project demonstrates how to execute service methods asynchronously in a Symfony application by simply marking them with a custom attribute `#[Async]`.

## Strategy

1. A PHP attribute `#[Async]` is added to service methods that should run asynchronously.
2. A custom compiler pass scans all services during container compilation.
3. For each service with an `#[Async]` method, the compiler pass generates a proxy class in `var/cache/async_proxies/` that implements the service interface.
4. The proxy intercepts calls to `#[Async]` methods and delegates them to a background process via the `AsyncRunner` service.
5. The `AsyncRunner` triggers a console command (`go:boost`) in a separate process using `exec()` to perform the actual execution of the method.

## How to Test

1. Add the `#[Async]` attribute to a service method (like in ArticleCreator), for example:

   ```php
   #[Async]
   public function create(string $title): void
   ```
2. Run the Symfony console command:

   ```bash
   symfony console app:create-article "Test Article"
   ```
3. The command should finish immediately while the actual operation runs in the background.
4. Check `var/log/article_async.log` to confirm that the method was executed asynchronously after the command completed.

PS: for now we need the service to implement an interface (classname."Interface") for the proxy generation to work.

## Expected Result

* The command returns quickly.
* The background process completes after a short delay.
* The log file shows the asynchronous operation execution.
