<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests as BaseThrottleRequests;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class CustomThrottleRequests extends BaseThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|string  ...$parameters
     * @return mixed
     *
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    public function handle($request, Closure $next, ...$parameters)
    {
        $key = $this->resolveRequestSignature($request);

        $maxAttempts  = $parameters[0] ?? 60; // 默认最大尝试次数
        $decaySeconds = $parameters[1] ?? 60; // 确保 $decaySeconds 是数字

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildJsonException($request, $key, $maxAttempts);
        }

        RateLimiter::hit($key, $decaySeconds);

        return $this->addHeaders(
            $next($request), $maxAttempts,
            RateLimiter::retriesLeft($key, $maxAttempts),
            RateLimiter::availableIn($key)
        );
    }

    /**
     * Resolve request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature($request)
    {
        return sha1($request->ip());
    }

    /**
     * Add the headers to the given response.
     *
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @param  int|null  $retryAfter
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addHeaders(Response $response, $maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remainingAttempts));

        if (! is_null($retryAfter)) {
            $response->headers->set('Retry-After', $retryAfter);
            $response->headers->set('X-RateLimit-Reset', $this->availableAt($retryAfter));
        }

        return $response;
    }

    /**
     * Get the number of seconds until the given DateTime.
     *
     * @param  \DateTime|int  $date
     * @return int
     */
    protected function availableAt($date = 0)
    {
        return $date instanceof \DateTime ? $date->getTimestamp() : $date;
    }

    /**
     * Build a JSON exception response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    protected function buildJsonException($request, $key, $maxAttempts)
    {
        // $retryAfter = RateLimiter::availableIn($key);

        return apiError('Too many requests, please try again later.', 10429);

    }
}
