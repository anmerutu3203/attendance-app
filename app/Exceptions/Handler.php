<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * APIルートでは404/403のエラーレスポンスをJSONに統一する。
     */
    public function render($request, Throwable $e)
    {
        if ($request->is('api/*')) {
            if ($e instanceof AuthorizationException) {
                return response()->json(['error' => 'この操作を行う権限がありません。'], 403);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json(['error' => '勤怠情報が見つかりませんでした。'], 404);
            }
        }

        return parent::render($request, $e);
    }

    /**
     * APIルートでは未認証時に必ずJSONで401を返す。
     */
    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse|RedirectResponse
    {
        if ($request->is('api/*')) {
            return response()->json(['message' => '認証されていません。'], 401);
        }

        return parent::unauthenticated($request, $exception);
    }
}
