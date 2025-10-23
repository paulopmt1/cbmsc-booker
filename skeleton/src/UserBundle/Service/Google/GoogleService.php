<?php

namespace UserBundle\Service\Google;

use AppBundle\Exception\HttpException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Response;

class GoogleService
{
    /**
     * @throws HttpException
     * @throws GuzzleException|HttpException
     *
     * @return GoogleUserDto
     */
    public static function getUserInfo(string $accessToken)
    {
        $client = new Client();
        try {
            $response = $client->request(
                'GET',
                'https://www.googleapis.com/oauth2/v1/userinfo?access_token='.$accessToken,
                [
                    'headers' => [
                        'Authorization' => \sprintf('Bearer %s', $accessToken),
                        'Accept' => 'application/json',
                    ],
                ]
            );

            if ($response->getStatusCode() >= 400) {
                throw new HttpException($response->getBody()->getContents());
            }

            $body = \GuzzleHttp\json_decode($response->getBody()->getContents());

            if (
                empty($body) || empty($body->id) || empty($body->email) || empty($body->given_name) || empty($body->picture)
            ) {
                throw new HttpException('Erro ao obter informações do google: '.\json_encode($body));
            }

            $firstName = $body->given_name;

            if (empty($body->family_name)) {
                $nameParser = \explode(' ', $body->given_name);
                $firstName = $nameParser[0];
                $lastName = \count($nameParser) > 1 ? \implode(', ', \array_slice($nameParser, 1)) : '';
            } else {
                $lastName = $body->family_name;
            }

            return new GoogleUserDto(
                $body->id,
                $body->email,
                $firstName,
                $lastName,
                $body->picture
            );
        } catch (\Exception $e) {
            throw new HttpException('Ocorreu um erro ao obter informações do usuário Google.', Response::HTTP_BAD_REQUEST, $e);
        }
    }
}
