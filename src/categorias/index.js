const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
const { withSelect } = wp.data;
const { Fragment } = wp.element;
const { PanelBody, Spinner, Placeholder, ToggleControl, TextControl, SelectControl, FormTokenField,Disabled } = wp.components;
const { InspectorControls } = wp.blockEditor;
import ServerSideRender from '@wordpress/server-side-render';
import { ReactComponent as Logo } from '../cluster.svg';

registerBlockType('rafax/cluster-categorias', {
    title: __('Rafax Cluster de categorías', 'rafax-cluster'),
    icon: { src: Logo },
    category: 'widgets',
    attributes: {
        showOnlyParent: {
            type: 'boolean',
            default: false
        },
        showParent: {
            type: 'string',
            default: 0
        },
        numberCats: {
            type: 'string',
            default: -1
        },
        hideEmpty: {
            type: 'boolean',
            default: false
        },
        excludeCats: {
            type: 'array',
            default: []
        },
        showDescription: {
            type: 'boolean',
            default: false
        },
        showCount: {
            type: 'boolean',
            default: false
        },
        targetBlank: {
            type: 'boolean',
            default: false
        }

    },

    edit: withSelect((select, { clientId }) => {

        let selectCore = select('core');

        const { showOnlyParent, excludeCats, hideEmpty, numberCats, showParent } = select('core/block-editor').getBlock(clientId).attributes;


        let queryArgs = {
            hide_empty: hideEmpty,
            per_page: numberCats > 0 ? numberCats : -1,
            exclude: excludeCats,
        };

        if (showOnlyParent) {

            queryArgs.parent = 0;
        }

        if (showParent > 0) {

            queryArgs.parent = showParent;
        }


        return {

            categories: selectCore.getEntityRecords('taxonomy', 'category', queryArgs),
            allCategories: selectCore.getEntityRecords('taxonomy', 'category', { per_page: -1 }),

        };

    })(({ allCategories, categories, attributes, setAttributes, blockProps }) => {


        if (!categories) {
            return (
                <div className="rafax-cluster-spinner">
                    <Spinner />
                    {__('Cargando...', 'rafax-cluster')}
                </div>
            );
        }
        let catsNames = [];
        let catsFieldValue = [];
        let categoryOptions = [];

        if (allCategories !== null) {
            catsNames = allCategories.map((cat) => cat.name);

            catsFieldValue = attributes.excludeCats.map((catId) => {
                let wantedCat = allCategories.find((cat) => {
                    return cat.id === catId;
                });
                if (wantedCat === undefined || !wantedCat) {
                    return false;
                }
                return wantedCat.name;
            });

            categoryOptions = allCategories.map(category => ({
                label: category.name,
                value: category.id,
            }));
        }
        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title={__('Opciones cluster categorías', 'rafax-cluster')} initialOpen={true}>
                        <ToggleControl
                            label={__('Mostrar solo categorías padre', 'rafax-cluster')}
                            checked={attributes.showOnlyParent}
                            onChange={(value) => setAttributes({ showOnlyParent: value })}
                        />
                        <ToggleControl
                            label={__('Ocultar categorías vacias', 'rafax-cluster')}
                            checked={attributes.hideEmpty}
                            onChange={(value) => setAttributes({ hideEmpty: value })}
                        />
                        <TextControl
                            label={__(
                                'Numero de categorías a devolver',
                                'rafax-cluster'
                            )}
                            help={__(
                                '0 para mostrar todas',
                                'rafax-cluster'
                            )}
                            value={attributes.numberPosts}
                            onChange={(value) => {

                                setAttributes({ numberCats: undefined === value ? 0 : value })
                            }}
                        />
                        <SelectControl
                            label={__(
                                'Mostrar categorías hijo de ',
                                'rafax-cluster'
                            )}
                            help={__(
                                'Id de la categoría padre',
                                'rafax-cluster'
                            )}
                            value={attributes.showParent}
                            options={[
                                { label: __('Buscar categoría', 'rafax-cluster'), value: 0 },
                                ...categoryOptions
                                // Agrega más opciones de categorías según sea necesario
                            ]}
                            onChange={(value) => {

                                setAttributes({ showParent: undefined === value ? 0 : value })
                            }}
                        />
                        <FormTokenField
                            label={__(
                                'Excluir categorías',
                                'rafax-cluster'
                            )}
                            help={__(
                                'Busca las categorías a excluir',
                                'rafax-cluster'
                            )}
                            value={catsFieldValue}
                            suggestions={catsNames}
                            onChange={(cats) => {
                                // Build array of selected posts.
                                let selectedCatsArray = [];
                                cats.map(
                                    (catName) => {
                                        const matchingCat = allCategories.find((cat) => {
                                            return cat.name === catName;
                                        });
                                        if (matchingCat !== undefined) {
                                            selectedCatsArray.push(matchingCat.id);
                                        }
                                    }
                                )
                                setAttributes({ excludeCats: selectedCatsArray });
                            }}
                        />
                    </PanelBody>
                    <PanelBody title={__('Opciones de visualizacion', 'rafax-cluster')} initialOpen={true}>
                        <ToggleControl

                            label={__('Abrir enlaces en una nueva ventana', 'rafax-cluster')}
                            checked={attributes.targetBlank}
                            onChange={(value) => setAttributes({ targetBlank: value })}
                        />
                        <ToggleControl
                            label={__('Mostrar contador de entradas', 'rafax-cluster')}
                            checked={attributes.showCount}
                            onChange={(value) => setAttributes({ showCount: value })}
                        />
                        <ToggleControl

                            label={__('Mostrar descripción de la categoría', 'rafax-cluster')}
                            checked={attributes.showDescription}
                            onChange={(value) => setAttributes({ showDescription: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                <Placeholder
                    icon="category"
                    label={__('Rafax Cluster categorías', 'rafax-cluster')}
                    instructions={__('Selecciona las opciones para mostrar las categorías en el cluster.', 'rafax-cluster')}
                >
                    <Disabled>
                        <ServerSideRender
                            block={'rafax/cluster-categorias'}
                            skipBlockSupportAttributes 
                            attributes={attributes}
                        />
                    </Disabled>
                    
                </Placeholder>
            </Fragment >
        );
    }),
    save: () => { return null; },
});

