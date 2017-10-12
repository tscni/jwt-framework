<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Jose\Bundle\Signature\Tests;

use Jose\Component\Core\Converter\StandardJsonConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSLoader;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group Bundle
 * @group Functional
 */
final class JWSComputationTest extends WebTestCase
{
    public function testCreateAndLoadAToken()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $jwk = JWK::create([
            'kty' => 'oct',
            'k' => '3pWc2vAZpHoV7XmCT-z2hWhdQquwQwW5a3XTojbf87c',
        ]);

        /** @var JWSBuilder $builder */
        $builder = $container->get('jose.jws_builder.builder1');

        /** @var JWSLoader $loader */
        $loader = $container->get('jose.jws_loader.loader1');

        $serializer = new CompactSerializer(new StandardJsonConverter());

        $jws = $builder
            ->create()
            ->withPayload('Hello World!')
            ->addSignature($jwk, [
                'alg' => 'HS256',
                'exp' => time() + 3600,
            ])
            ->build();
        $token = $serializer->serialize($jws, 0);

        $loaded = $loader->load($token);
        $index = $loader->verifyWithKey($loaded, $jwk);
        self::assertEquals(0, $index);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unable to verify the JWS.
     */
    public function testUnableToLoadAnExpiredToken()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $jwk = JWK::create([
            'kty' => 'oct',
            'k' => '3pWc2vAZpHoV7XmCT-z2hWhdQquwQwW5a3XTojbf87c',
        ]);

        /** @var JWSBuilder $builder */
        $builder = $container->get('jose.jws_builder.builder1');

        /** @var JWSLoader $loader */
        $loader = $container->get('jose.jws_loader.loader1');

        $serializer = new CompactSerializer(new StandardJsonConverter());

        $jws = $builder
            ->create()
            ->withPayload('Hello World!')
            ->addSignature($jwk, [
                'alg' => 'HS256',
                'exp' => time() - 3600,
            ])
            ->build();
        $token = $serializer->serialize($jws, 0);

        $loaded = $loader->load($token);
        $loader->verifyWithKey($loaded, $jwk);
    }
}