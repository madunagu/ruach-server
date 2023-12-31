<?php

namespace Tests\Traits;

use App\Models\User;

trait AttachJwtToken
{
    /**
     * @var User
     */
    protected $loginUser;
    /**
     * @param User $user
     * @return $this
     */
    public function loginAs(User $user)
    {
        $this->loginUser = $user;
        return $this;
    }
    /**
     * @return string
     */
    protected function getJwtToken(): String
    {
        $user = $this->loginUser ?: User::factory()->create();
        $token =    $user->createToken('ruach-test');
        return  $token->plainTextToken;
    }
    /**
     * @param string $method
     * @param string $uri
     * @param array $parameters
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param string $content
     * @return \Illuminate\Http\Response
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        if ($this->requestNeedsToken($method, $uri)) {
            $server = $this->attachToken($server);
        }
        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
    /**
     * @param string $method
     * @param string $uri
     * @return bool
     */
    protected function requestNeedsToken($method, $uri)
    {
        return !('/auth/login' === $uri && 'POST' === $method);
    }
    /**
     * @param array $server
     * @return string
     */
    protected function attachToken(array $server)
    {
        return array_merge($server, $this->transformHeadersToServerVars([
            'Authorization' => 'Bearer ' . $this->getJwtToken(),
        ]));
    }
}
