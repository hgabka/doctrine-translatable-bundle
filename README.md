# hgabka/doctrine-translatable-bundle

Symfony bundle a [`hgabka/doctrine-translatable`](../doctrine-translatable) Doctrine kiegészítéshez: beköti a `TranslatableListener`-t, a `jms/metadata` driver láncot, és a kérés nyelvét automatikusan átadja a listenernek.

## Telepítés

```bash
composer require hgabka/doctrine-translatable-bundle
```

```php
// config/bundles.php
Hgabka\Doctrine\TranslatableBundle\HgabkaDoctrineTranslatableBundle::class => ['all' => true],
```

Követelmény: Symfony 7/8 FrameworkBundle, Doctrine ORM (a bundle az összes `doctrine.entity_managers`-hez felveszi a saját metadata drivert).

## Konfiguráció

```yaml
# config/packages/hgabka_doctrine_translatable.yaml
hgabka_doctrine_translatable:
    fallback_locale: en   # alapértelmezés
```

## Mit csinál a bundle?

- **`DriverChainCompilerPass`** – minden entity managerhez egy `DoctrineAdapter` drivert ad a `Metadata\Driver\DriverChain`-hez, így a translatable metaadat a Doctrine mappingből (attribútumokból) olvasható.
- **`Metadata\MetadataFactory`**, **`TranslatableListener`** – szolgáltatásként regisztrálva (autowire).
- **`EventListener\LocaleListener`** – `kernel.request` (prioritás: 10) eseményen a `TranslatableListener::setCurrentLocale()`-t a request nyelvére állítja.
- **`Filter\TranslatableFilter`** – Sonata Admin szűrő (`translatable` alias, a `Resources/config/sonata.yaml`-ban van regisztrálva) fordított mezők szerinti kereséshez: az aktuális nyelvű translation-re joinol (`trans` alias), és `LIKE`/`=`/`<>` operátorokkal szűr.

Az entitások definiálásához lásd a [`doctrine-translatable` README-t](../doctrine-translatable/README.md); a `translate($locale)` kényelmi metódust adó trait a `hgabka/utils-bundle`-ben van (`Hgabka\UtilsBundle\Traits\TranslatableTrait`).
