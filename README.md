# terminal42/contao-dam-integrator

This bundle integrates different Asset Management solutions directly in to Contao. It works by adding new tabs next to
the regular file picker tabs where you can choose your assets of your configured Asset Management providers. You can 
search and filter your assets, download and then select them right from the file tree widget.

Currently, there are two integrations supported:

* Bynder (https://www.bynder.com)
* Celum (https://www.celum.com) via the brix:anura Celum extension (https://www.brix.ch/de/extensions/brixanura)

## Why would I need this, if it still downloads all the files to the system?

This approach still has one big advantage which is that you can manage your files in one central location and use it 
across multiple content management systems or even multiple Contao setups.

## Installation

1) Install the bundle

```
$ composer require terminal42/contao-dam-integrator
```

2) Configure the bundle

Edit your `config.yml` file and add the necessary configuration parameters. Each DAM provider has different
options, so make sure to pick yours accordingly. Of course, you can enable more than just one DAM provider and
give your users the option to work with multiple. Also ensure to enable the DAM integrations in the user group settings
in the back end!

Some of the settings are identical amongst the different providers, just the values inside might differ. The common
settings are described here:

* `metadata.mapper`: The integrations allow you to import metadata in a way that is perfect to you. As all DAM providers
  support configuring custom properties, importing the ones you like is done using a Twig template string. That also allows
  you to combine multiple fields or use Twig functions for advanced use cases. Note that because we are within the Symfony
  config, if you are using `{% if ... %}` statements, you need to escape the `%` in order to make sure Symfony is not
  looking for parameters. So write `{%% if ... %%}` instead.
* `metadata.cronjob.expression`: By default, cronjobs are disabled but if you want to have this extension to regularly update the
  metadata of your assets, you may configure the expression here. For example, if you wanted to update the metadata of all
  files every Monday, use e.g.:
  ```yaml
   metadata:
      cronjob:
         expression: '42 5 * * 1'
   ```
   Note that the cronjob will not do anything if there is no `metadata.mapper` configured. The assets are queued using
   Contao's built-in Symfony Messenger integration so updating will happen in the background. To speed things up and make
   it more reliable it is recommended that you configure background workers (or Contao's Cronjob Framework which will
   do that for you).

### Bynder

First, configure your Bynder account. Create a permanent token and configure this as `token`.
In order to not download huge (both in size and in pixels), configure a "Derivative" in Bynder and use its name as `derivative_name`.

```yaml
terminal42_contao_dam_integrator:
    bynder:
        domain: 'foobar.getbynder.com'
        # You can get the permanent token as described on https://support.bynder.com/hc/en-us/articles/360013875300-Permanent-Tokens
        token: '2a7a5243548…32739e624dc'
        # The target dir the bundle downloads assets to. Make sure it is RELATIVE to your specified contao.upload_path (In that case it would be default store the images in "files/bynder_assets")
        target_dir: 'bynder_assets'
        derivative_name: Contao
        metadata:
            mapper:
                de:
                    title: '{{ name }}'
                    alt: "{{ tags|join(', ') }}"
                    caption: '© {{ property_copyright }}. {{ property_lizenzart }}'
            cronjob:
                expression: '42 5 * * 1'
```

With the `metadata.mapper` you can configure the way you want to import metadata from Bynder to Contao's metadata widget.
The Bynder integration automatically converts your custom media properties and fetches the correct values based on the language
for it. It will also check if the media property is configured to allow multiple or only single values. So you don't have to worry
about converting arrays with only one value into a string. The templates expect a valid Twig string so you can also 

### Celum

First, configure your Celum account. Make sure that you have the brix:anura extension installed. This bundle relies on its API.
In order to not download huge (both in size and in pixels), configure a "Download Format" in Celum and use its ID as `download_format_id`.

```yaml
terminal42_contao_dam_integrator:
    celum:
        base_uri: 'https://dam.example.com/anura/'
        token: 'nTLf…kosEtr90qVk'
        # The target dir the bundle downloads assets to. Make sure it is RELATIVE to your specified contao.upload_path (In that case it would be default store the images in "files/celum_assets")
        target_dir: 'celum_assets'
        download_format_id: 42
        metadata:
            mapper:
                de:
                    title: '{{ name }}'
                    alt: "{{ info_371|default('') }}" # Info field ID 371 is "Description" in our case
                    caption: '{%% if info_373 ?? false %%}© {{ info_373 }} {%% endif %%}'  # Note the escaping of "%" if you work with if statements
            cronjob:
                expression: '42 5 * * 1'
```

With the `metadata.mapper` you can configure the way you want to import metadata from Celum to Contao's metadata widget.
All the asset details from `general` as well as all the info field values are available.

## Upgrade from `terminal42/contao-bynder`

This bundle is the successor of `terminal42/contao-bynder`. Contrary to the other bundle,
this bundle provides integrations with more DAM providers than just Bynder. To make the
migration from `terminal42/contao-bynder` to `terminal42/contao-dam-integrator` easy, this
bundle ships with an automated migration:

1. Make sure you have updated to version 2 of `terminal42/contao-bynder` before you run the migration.
2. Uninstall `terminal42/contao-bynder` - do **not** run database migrations.
3. Install `terminal42/contao-dam-integrator` - do **not** run database migrations.
4. Migrate your `config.yaml` from the old format, to the new format. The config keys are almost the same:
   1. Migrate from `terminal42_contao_bynder` to `terminal42_contao_dam_integrator.bynder`.
   2. Move the `metaDataMapper` to `metadata.mapper`.
   3. Convert from camel case to snake case (e.g. `derivativeName` -> `derivative_name`). 
   4. Support for `derivativeOptions` has been dropped so "on the fly" derivatives are not supported anymore. Configure
      a permanent derivative instead and use this using `derivative_name`.
5. Run `contao:migrate` (or use the Contao Manager to run database migrations)
6. Enable the integrations in the user groups. By default, all integrations are now hidden to non-admin users.