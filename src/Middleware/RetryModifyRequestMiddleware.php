<?php

namespace Frankkessler\Guzzle\Oauth2\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;

/**
 * Middleware that retries requests based on the boolean result of
 * invoking the provided "decider" function.
 */
class RetryModifyRequestMiddleware
{
    /** @var callable */
    private $nextHandler;

    /** @var callable */
    private $decider;

    /**
     * @param callable $decider         Function that accepts the number of retries,
     *                                  a request, [response], and [exception] and
     *                                  returns true if the request is to be
     *                                  retried.
     * @param callable $requestModifier Function to modify request
     * @param callable $nextHandler     Next handler to invoke.
     * @param callable $delay           Function that accepts the number of retries
     *                                  and returns the number of milliseconds to
     *                                  delay.
     */
    public function __construct(
        callable $decider,
        callable $requestModifier,
        callable $nextHandler,
        callable $delay = null
    ) {
        $this->decider = $decider;
        $this->requestModifier = $requestModifier;
        $this->nextHandler = $nextHandler;
        $this->delay = $delay ?: __CLASS__.'::exponentialDelay';
    }

    /**
     * Default exponential backoff delay function.
     *
     * @param $retries
     *
     * @return int
     */
    public static function exponentialDelay($retries)
    {
        return (int) pow(2, $retries - 1);
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        if (!isset($options['retries'])) {
            $options['retries'] = 0;
        }

        $fn = $this->nextHandler;

        return $fn($request, $options)
            ->then(
                $this->onFulfilled($request, $options),
                $this->onRejected($request, $options)
            );
    }

    private function onFulfilled(RequestInterface $req, array $options)
    {
        return function ($value) use ($req, $options) {
            if (!call_user_func_array(
                $this->decider,
                [
                    $options['retries'],
                    $req,
                    $value,
                    null,
                ]
            )) {
                return $value;
            }
            $req = call_user_func_array(
                $this->requestModifier,
                [
                    $req,
                    $value,
                ]
            );

            return $this->doRetry($req, $options);
        };
    }

    private function onRejected(RequestInterface $req, array $options)
    {
        return function ($reason) use ($req, $options) {
            if (!call_user_func(
                $this->decider, [
                    $options['retries'],
                    $req,
                    null,
                    $reason,
                ])
            ) {
                return new RejectedPromise($reason);
            }
            $req = call_user_func_array($this->requestModifier, [$req, null]);

            return $this->doRetry($req, $options);
        };
    }

    private function doRetry(RequestInterface $request, array $options)
    {
        $options['delay'] = call_user_func($this->delay, ++$options['retries']);

        return $this($request, $options);
    }
}
