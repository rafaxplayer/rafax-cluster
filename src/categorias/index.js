const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
const { withSelect } = wp.data;
const { Fragment } = wp.element;
const { PanelBody, RangeControl, Spinner, Placeholder, ToggleControl, FormTokenField, Disabled } = wp.components;
const { InspectorControls } = wp.blockEditor;

import ServerSideRender from '@wordpress/server-side-render';

import { postCategories } from '@wordpress/icons';

import { BlockStyles, SelectorCats, Loading } from '../sharecomponents';

registerBlockType('rafax/cluster-categorias', {
    title: __('Rafax Cluster de categorías', 'rafax-cluster'),
    icon: { src: postCategories },
    category: 'rafax-blocks',
    attributes: {
        showOnlyParent: {
            type: 'boolean',
            default: false
        },
        showParent: {
            type: 'string',
            default: 0
        },
        showImages: {
            type: 'boolean',
            default: false
        },
        numberCats: {
            type: 'string',
            default: 100
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
        },
        styleGrid: {
            type: 'string',
            default: 'grid-cols-3'
        }

    },

    edit: withSelect((select, { clientId }) => {

        const selectCore = select('core');
        const blockEditor = select('core/block-editor');

        const { showOnlyParent, excludeCats, hideEmpty, numberCats, showParent } = blockEditor.getBlock(clientId).attributes;

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

    })(({ allCategories, categories, attributes, setAttributes }) => {

        const isloading = !categories;

        const { excludeCats, showImages, numberCats, hideEmpty, showDescription, showCount, showOnlyParent } = attributes;

        const handleChangeShowOnlyParent = (value) => setAttributes({ showOnlyParent: value });
        const handleChangeHideEmpty = (value) => setAttributes({ hideEmpty: value });
        const handleChangeShowImages = (value) => setAttributes({ showImages: value });
        const handlenumberCats = (value) => setAttributes({ numberCats: String(value) });
        const handleshowParent = (value) => setAttributes({ showParent: undefined === value ? 0 : value });
        const handleTargetBlank = (value) => setAttributes({ targetBlank: value });
        const handleshowCount = (value) => setAttributes({ showCount: value });
        const handleshowdescription = (value) => setAttributes({ showDescription: value });


        let catsNames = [];
        let catsFieldValue = [];
        let categoryOptions = [];

        if (allCategories !== null) {

            catsNames = allCategories?.map((cat) => cat.name) || [];
            catsFieldValue = excludeCats?.map((catId) => allCategories.find((cat) => cat.id === catId)?.name || false) || [];
            categoryOptions = allCategories?.map(category => ({ label: category.name, value: category.id })) || [];

        }

        return (
            <Fragment>
                <BlockStyles setAttributes={setAttributes} />

                <InspectorControls>
                    <PanelBody title={__('Opciones cluster categorías', 'rafax-cluster')} initialOpen={true}>
                        <ToggleControl
                            label={__('Mostrar solo categorías padre', 'rafax-cluster')}
                            checked={showOnlyParent}
                            onChange={handleChangeShowOnlyParent}
                        />
                        <ToggleControl
                            label={__('Ocultar categorías vacias', 'rafax-cluster')}
                            checked={hideEmpty}
                            onChange={handleChangeHideEmpty}
                        />
                        <ToggleControl
                            label={__('Mostrar imagenes', 'rafax-cluster')}
                            checked={showImages}
                            onChange={handleChangeShowImages}
                        />
                        {showImages && <>
                            <a href={phpData.pSetUrl}>{__('Instrucciones para mostrar imagenes', 'rafax-cluster')}</a>
                        </>}
                        <RangeControl
                            label={__(
                                'Numero de categorías a devolver',
                                'rafax-cluster'
                            )}
                            value={parseInt(numberCats)}
                            onChange={handlenumberCats}
                            min={1}
                            max={100}

                        />

                        <SelectorCats
                            categories={allCategories}
                            label={__(
                                'Mostrar categorías hijo de ',
                                'rafax-cluster'
                            )}
                            help={__(
                                'Id de la categoría padre',
                                'rafax-cluster'
                            )}
                            attributes={attributes}
                            defaultItem={{ label: __('Buscar categoría', 'rafax-cluster'), value: 0 }}
                            value={attributes.showParent}
                            onChange={handleshowParent} />


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
                            onChange={handleTargetBlank}
                        />
                        <ToggleControl
                            label={__('Mostrar contador de entradas', 'rafax-cluster')}
                            checked={showCount}
                            onChange={handleshowCount}
                        />
                        <ToggleControl

                            label={__('Mostrar descripción de la categoría', 'rafax-cluster')}
                            checked={showDescription}
                            onChange={handleshowdescription}
                        />
                    </PanelBody>
                </InspectorControls>
                <Placeholder
                    icon="category"
                    label={__('Rafax Cluster categorías', 'rafax-cluster')}
                    instructions={__('Selecciona las opciones para mostrar las categorías en el cluster.', 'rafax-cluster')}
                >
                    {isloading && <Loading label={__('Cargando...', 'rafax-cluster')}/>}
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

