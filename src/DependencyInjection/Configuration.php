<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\DependencyInjection;

use Cron\CronExpression;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('terminal42_contao_dam_integrator');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->append($this->addBynderNode())
                ->append($this->addCelumNode())
            ->end()
        ;

        return $treeBuilder;
    }

    private function addBynderNode(): NodeDefinition
    {
        return (new TreeBuilder('bynder'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
            ->children()
                ->scalarNode('domain')
                    ->info('The domain of your Bynder account (without the protocol).')
                    ->example('foobar.getbynder.com')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('token')
                    ->info('You can get the permanent token as described on https://support.bynder.com/hc/en-us/articles/360013875300-Permanent-Tokens.')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('derivative_name')
                    ->info('The derivative_name contains the derivative you added in Bynder. It will be used to fetch a derivative of the original when downloading it to your Contao installation.')
                    ->example('contao_derivative')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->append($this->addTargetDirNode('bynder'))
                ->append($this->addMetadataNode())
            ->end()
        ;
    }

    private function addCelumNode(): NodeDefinition
    {
        return (new TreeBuilder('celum'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
            ->children()
                ->scalarNode('base_uri')
                    ->info('The base URI of your Celum Anura API endpoint. With protocol and path.')
                    ->example('https://dam.example.com/anura/')
                    ->cannotBeEmpty()
                    ->isRequired()
                    ->beforeNormalization()
                        ->always(
                            static function (string $baseUri) {
                                return trim($baseUri, '/').'/'; // Ensure path relative
                            },
                        )
                    ->end()
                ->end()
                ->scalarNode('token')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->integerNode('download_format_id')
                    ->isRequired()
                ->end()
                ->append($this->addTargetDirNode('celum'))
                ->append($this->addMetadataNode())
            ->end()
        ;
    }

    private function addTargetDirNode(string $integration): ScalarNodeDefinition
    {
        $node = new ScalarNodeDefinition('target_dir');
        $default = $integration.'_assets';

        $node
            ->info('The target directory the bundle downloads assets to. Make sure it is RELATIVE to your specified contao.upload_path.')
            ->example(\sprintf('%s (by default will turn into %%contao.upload_path%%/%s', $default, $default))
            ->cannotBeEmpty()
            ->defaultValue($default)
            ->end()
        ;

        return $node;
    }

    private function addMetadataNode(): NodeDefinition
    {
        return (new TreeBuilder('metadata'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->variableNode('mapper')
                    ->defaultValue([])
                    ->treatNullLike([])
                ->end()
                ->arrayNode('cronjob')
                ->addDefaultsIfNotSet()
                ->canBeEnabled()
                    ->children()
                        ->scalarNode('expression')
                            ->info('The cronjob expression of when the cronjob should run.')
                            ->example('42 5 * * 1')
                            ->cannotBeEmpty()
                            ->isRequired()
                            ->validate()
                                ->ifTrue(static fn (string $expression) => !CronExpression::isValidExpression($expression))
                                ->thenInvalid('Invalid cronjob expression: %s')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
