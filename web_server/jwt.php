<?php

declare(strict_types=1);

namespace JWT;

use DateTimeImmutable;
use DateTimeZone;
use DB\DB;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Blake2b;
use Lcobucci\JWT\Signer\Key\FileCouldNotBeRead;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\HasClaim;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\RelatedTo;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\Validator;
use Ramsey\Uuid\Uuid;

use function Error\logError;

require_once 'error.php';

class JWT
{
    private $algorithm;
    private $signingKey;

    public function __construct(Signer $signer, InMemory $key)
    {
        $this->algorithm = $signer;
        $this->signingKey = $key;
    }

    public static function init(): JWT|false
    {
        try {
            $jwt = new JWT(new Blake2b(), InMemory::file("secret.key"));
        } catch (FileCouldNotBeRead) {
            $db = DB::init();
            if ($db === null) return false;
            logError($db, "Failed to read secret. Does secret.key exist?");
            return false;
        }
        return $jwt;
    }

    public function issueRefreshToken(int $uid): UnencryptedToken
    {
        $tokenBuilder = Builder::new(new JoseEncoder(), ChainedFormatter::default());
        $now   = new DateTimeImmutable("now", (new DateTimeZone("UTC")));
        $token = $tokenBuilder
            // Configures the issuer (iss claim)
            ->issuedBy('https://tasks.website')
            // Configures the audience (aud claim)
            ->permittedFor('https://tasks.website')
            // Configures the subject of the token (sub claim)
            ->relatedTo('Refresh')
            // Configures the id (jti claim)
            ->identifiedBy(Uuid::uuid4()->toString())
            // Configures the time that the token was issue (iat claim)
            ->issuedAt($now)
            // Configures the time that the token can be used (nbf claim)
            ->canOnlyBeUsedAfter($now)
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($now->modify('+7 day'))
            // Configures a new claim, called "uid"
            ->withClaim('uid', $uid)
            // Configures a new header, called "foo"
            // ->withHeader('foo', 'bar')
            // Builds a new token
            ->getToken($this->algorithm, $this->signingKey);

        return $token;
    }

    public function issueAccessToken(int $uid): UnencryptedToken
    {
        $tokenBuilder = Builder::new(new JoseEncoder(), ChainedFormatter::default());
        $now   = new DateTimeImmutable("now", (new DateTimeZone("UTC")));
        $token = $tokenBuilder
            // Configures the issuer (iss claim)
            ->issuedBy('https://tasks.website')
            // Configures the audience (aud claim)
            ->permittedFor('https://tasks.website')
            // Configures the subject of the token (sub claim)
            ->relatedTo('Access')
            // Configures the id (jti claim)
            ->identifiedBy(Uuid::uuid4()->toString())
            // Configures the time that the token was issue (iat claim)
            ->issuedAt($now)
            // Configures the time that the token can be used (nbf claim)
            ->canOnlyBeUsedAfter($now)
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($now->modify('+5 minute'))
            // Configures a new claim, called "uid"
            ->withClaim('uid', $uid)
            // Configures a new header, called "foo"
            // ->withHeader('foo', 'bar')
            // Builds a new token
            ->getToken($this->algorithm, $this->signingKey);

        return $token;
    }

    public function parseToken(string $token_str): UnencryptedToken|false
    {
        $parser = new Parser(new JoseEncoder());

        try {
            return $parser->parse($token_str);
        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound) {
            return false;
        }
    }

    public function validateRefreshToken(UnencryptedToken $token): bool
    {
        $validator = new Validator();

        if ($validator->validate($token, new IssuedBy("https://tasks.website")) === false) return false;
        if ($validator->validate($token, new PermittedFor("https://tasks.website")) === false) return false;
        if ($validator->validate($token, new RelatedTo("Refresh")) === false) return false;
        if ($validator->validate($token, new SignedWith($this->algorithm, $this->signingKey)) === false) return false;
        if ($validator->validate($token, new StrictValidAt(SystemClock::fromUTC())) === false) return false;
        if ($validator->validate($token, new HasClaim("uid")) === false) return false;

        return true;
    }

    public function validateAccessToken(UnencryptedToken $token): bool
    {
        $validator = new Validator();

        if ($validator->validate($token, new IssuedBy("https://tasks.website")) === false) return false;
        if ($validator->validate($token, new PermittedFor("https://tasks.website")) === false) return false;
        if ($validator->validate($token, new RelatedTo("Access")) === false) return false;
        if ($validator->validate($token, new SignedWith($this->algorithm, $this->signingKey)) === false) return false;
        if ($validator->validate($token, new StrictValidAt(SystemClock::fromUTC())) === false) return false;
        if ($validator->validate($token, new HasClaim("uid")) === false) return false;

        return true;
    }
}
