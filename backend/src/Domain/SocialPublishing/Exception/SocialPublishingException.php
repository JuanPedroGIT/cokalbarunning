<?php

declare(strict_types=1);

namespace App\Domain\SocialPublishing\Exception;

use Exception;

final class SocialPublishingException extends Exception
{
    public static function networkNotSupported(string $network): self
    {
        return new self(sprintf('La red social "%s" no está soportada.', $network));
    }

    public static function alreadyPublished(string $network): self
    {
        return new self(sprintf('Este post ya ha sido publicado en "%s".', $network));
    }

    public static function webhookFailed(string $reason): self
    {
        return new self(sprintf('Error al llamar al webhook de n8n: %s', $reason));
    }

    public static function postNotFound(): self
    {
        return new self('El post no existe.');
    }

    public static function logNotFound(): self
    {
        return new self('El registro de publicación no existe.');
    }

    public static function invalidCallbackToken(): self
    {
        return new self('Token de callback inválido.');
    }

    public static function missingCoverImage(): self
    {
        return new self('El post no tiene imagen de portada, es obligatoria para publicar en Instagram.');
    }

    public static function postTypeNotAllowedForNetwork(): self
    {
        return new self('Este tipo de noticia no se puede publicar en redes sociales.');
    }
}
