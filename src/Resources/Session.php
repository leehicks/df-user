<?php

namespace DreamFactory\Core\User\Resources;

use DreamFactory\Core\Exceptions\RestException;
use DreamFactory\Core\System\Resources\UserSessionResource;

class Session extends UserSessionResource
{
    /**
     *{@inheritdoc}
     */
    protected function handlePOST()
    {
        /** @var \DreamFactory\Core\User\Services\User $userService */
        $userService = $this->getService();
        if (!$this->isOAuthCallback() && $userService->handlesAlternateAuth()) {
            try {
                $authenticator = $userService->getAltAuthenticator();

                return $authenticator->handleLogin($this->request);
            } catch (\Exception $e) {
                throw new RestException(
                    $e->getCode(),
                    'Failed to perform alternate authentication. ' . $e->getMessage()
                );
            }
        }

        return parent::handlePOST();
    }

    /**
     * Checks to see if this is an OAuth callback request
     *
     * @return bool
     */
    protected function isOAuthCallback()
    {
        return $this->request->input('oauth_callback', false);
    }
}