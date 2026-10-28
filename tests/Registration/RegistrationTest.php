<?php

declare(strict_types=1);

namespace Test\ClientZone;

use ClientZone\Customer;
use ClientZone\Registration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ClientZone\Registration
 */
final class RegistrationTest extends TestCase
{
    private const SECRET = 'unit-test-secret-0123456789abcdefghij';

    private const FLAGS = ['REGISTER_USERNAME_IS_EMAIL', 'REGISTER_VERIFY_EMAIL', 'ABRAFLEXI_PASSWORD_HASH', 'ABRAFLEXI_PASSWORD_SALT', 'REGISTRATION_SECRET'];

    protected function setUp(): void
    {
        foreach (self::FLAGS as $flag) {
            putenv($flag);
        }

        putenv('REGISTRATION_SECRET='.self::SECRET);
    }

    protected function tearDown(): void
    {
        $this->setUp();
    }

    public function testFlagDefaults(): void
    {
        self::assertTrue(Registration::usernameIsEmail());
        self::assertFalse(Registration::verifyEmail());
    }

    /**
     * @dataProvider flagValues
     */
    public function testFlagsAreParsed(string $value, bool $expected): void
    {
        putenv('REGISTER_USERNAME_IS_EMAIL='.$value);
        putenv('REGISTER_VERIFY_EMAIL='.$value);

        self::assertSame($expected, Registration::usernameIsEmail());
        self::assertSame($expected, Registration::verifyEmail());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function flagValues(): iterable
    {
        yield 'true' => ['true', true];
        yield 'TRUE' => ['TRUE', true];
        yield 'one' => ['1', true];
        yield 'yes' => ['yes', true];
        yield 'false' => ['false', false];
        // '0' is not covered: Ease\Shared::cfg() treats an empty-looking env var as unset (.env constants are fine).
        yield 'garbage' => ['maybe', false];
    }

    public function testTokenRoundTrip(): void
    {
        $data = ['email' => 'jan@example.com', 'state' => Registration::STATE_NEW, 'nazev' => 'Čeština s.r.o.'];
        $parsed = Registration::parseToken(Registration::makeToken($data));

        self::assertNotNull($parsed);
        self::assertSame('jan@example.com', $parsed['email']);
        self::assertSame('Čeština s.r.o.', $parsed['nazev']);
        self::assertGreaterThan(time(), $parsed['exp']);
    }

    public function testTokenIsUrlSafe(): void
    {
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', Registration::makeToken(['email' => 'a@b.cz']));
    }

    public function testTokenDoesNotLeakPayload(): void
    {
        $token = Registration::makeToken(['email' => 'secret@example.com', 'passwordFields' => ['password' => 'hunter22hunter22']]);

        self::assertStringNotContainsString('hunter22', base64_decode(strtr($token, '-_', '+/')));
        self::assertStringNotContainsString('secret@example.com', base64_decode(strtr($token, '-_', '+/')));
    }

    public function testTokenNeedsDedicatedSecret(): void
    {
        $token = Registration::makeToken(['email' => 'a@b.cz']);

        putenv('REGISTRATION_SECRET=too-short');

        self::assertFalse(Registration::tokenSecretConfigured());
        self::assertNull(Registration::parseToken($token));
        $this->expectException(\RuntimeException::class);
        Registration::makeToken(['email' => 'a@b.cz']);
    }

    public function testTokenWithAnotherSecretIsRejected(): void
    {
        $token = Registration::makeToken(['email' => 'a@b.cz']);

        putenv('REGISTRATION_SECRET=another-secret-0123456789abcdefghijk');

        self::assertNull(Registration::parseToken($token));
    }

    public function testTamperedTokenIsRejected(): void
    {
        $token = Registration::makeToken(['email' => 'a@b.cz']);

        self::assertNull(Registration::parseToken($token.'A'));
        self::assertNull(Registration::parseToken(substr_replace($token, $token[30] === 'A' ? 'B' : 'A', 30, 1)));
        self::assertNull(Registration::parseToken(''));
        self::assertNull(Registration::parseToken('garbage'));
    }

    public function testExpiredTokenIsRejected(): void
    {
        $key = (new \ReflectionMethod(Registration::class, 'tokenKey'))->invoke(null);
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt(json_encode(['email' => 'a@b.cz', 'exp' => time() - 1]), 'aes-256-gcm', $key, \OPENSSL_RAW_DATA, $iv, $tag);
        $token = rtrim(strtr(base64_encode($iv.$tag.$cipher), '+/', '-_'), '=');

        self::assertNull(Registration::parseToken($token));
    }

    public function testDetailsAreAskedOnlyForUnknownEmail(): void
    {
        self::assertSame(
            [Registration::STEP_EMAIL, Registration::STEP_COMPANY, Registration::STEP_ACCESS, Registration::STEP_DONE],
            Registration::steps(Registration::STATE_NEW),
        );

        foreach ([Registration::STATE_KNOWN, Registration::STATE_COMPANY, null] as $state) {
            self::assertSame(
                [Registration::STEP_EMAIL, Registration::STEP_ACCESS, Registration::STEP_DONE],
                Registration::steps($state),
            );
        }
    }

    public function testCompanyMatchAlwaysNeedsVerification(): void
    {
        self::assertFalse(Registration::mustVerify(Registration::STATE_KNOWN));
        self::assertFalse(Registration::mustVerify(Registration::STATE_NEW));
        self::assertTrue(Registration::mustVerify(Registration::STATE_COMPANY));

        putenv('REGISTER_VERIFY_EMAIL=true');

        self::assertTrue(Registration::mustVerify(Registration::STATE_KNOWN));
    }

    public function testPasswordFieldsPlain(): void
    {
        self::assertSame(['password' => 'abc12345'], Customer::passwordWriteFields('abc12345'));
    }

    public function testPasswordFieldsHashedWithSalt(): void
    {
        putenv('ABRAFLEXI_PASSWORD_HASH=sha256');
        putenv('ABRAFLEXI_PASSWORD_SALT=pepper');

        self::assertSame(
            ['password' => hash('sha256', 'pepper:abc12345'), 'password@hash' => 'sha256', 'password@salt' => 'pepper'],
            Customer::passwordWriteFields('abc12345'),
        );
    }
}
