const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
import { ReactComponent as Logo } from '../cluster.svg';
const { withSelect } = wp.data;
const { Fragment } = wp.element;
const { PanelBody, Spinner, Placeholder, ToggleControl, TextControl, FormTokenField } = wp.components;
const { InspectorControls } = wp.blockEditor;

registerBlockType('rafax/cluster-categorias', {
    title: __('Rafax Cluster de categorias', 'rafax-cluster'),
    icon: { src: Logo },
    category: 'widgets',
    attributes: {
        showOnlyParent: {
            type: 'boolean',
            default: false
        },
        showParent:{
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
        targetBlank:{
            type: 'boolean',
            default: false
        }

    },

    edit: withSelect((select, { clientId }) => {

        let selectCore = select('core');

        const { showOnlyParent, excludeCats, hideEmpty, numberCats,showParent } = select('core/block-editor').getBlock(clientId).attributes;

        let queryArgs = {
            hide_empty: hideEmpty,
            per_page: numberCats > 0 ? numberCats : -1,
            exclude: excludeCats,
            
        };

        if (showOnlyParent) {

            queryArgs.parent = 0;
        }

        if (showParent>0) {

            queryArgs.parent = showParent;
        }
        

        return {

            categories: selectCore.getEntityRecords('taxonomy', 'category', queryArgs),
            allCategories: selectCore.getEntityRecords('taxonomy', 'category', { per_page: -1 }),

        };

    })(({ allCategories, categories, attributes, setAttributes }) => {

        console.log(categories);
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
        }

        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title={__('Opciones cluster Categorias', 'rafax-cluster')} initialOpen={true}>
                        <ToggleControl
                            label={__('Mostrar solo categorias padre', 'rafax-cluster')}
                            checked={attributes.showOnlyParent}
                            onChange={(value) => setAttributes({ showOnlyParent: value })}
                        />
                        <ToggleControl
                            label={__('Ocultar categorias vacias', 'rafax-cluster')}
                            checked={attributes.hideEmpty}
                            onChange={(value) => setAttributes({ hideEmpty: value })}
                        />
                        <TextControl
                            label={__(
                                'Numero de categorias a devolver',
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
                        <TextControl
                            label={__(
                                'Mostrar categorias hijo de ',
                                'rafax-cluster'
                            )}
                            help={__(
                                'Id de la categoria padre',
                                'rafax-cluster'
                            )}
                            value={attributes.showParent}
                            onChange={(value) => {

                                setAttributes({ showParent: undefined === value ? 0 : value })
                            }}
                        />
                        <FormTokenField
							label={__(
								'Excluir Categorias',
								'rafax-cluster'
							)}
							help={__(
								'Incluye la id de las categorias a excluir separadas por coma',
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
								setAttributes({ excludeCats:selectedCatsArray });
							}}
						/> 
                        <ToggleControl
                            label={__('Abrir enlaces en una nueva ventana', 'rafax-cluster')}
                            checked={attributes.targetBlank}
                            onChange={(value) => setAttributes({ targetBlank: value })}
                        />

                    </PanelBody>
                </InspectorControls>
                <Placeholder
                    icon="admin-post"
                    label={__('Rafax Cluster categorias', 'rafax-cluster')}
                    instructions={__('Selecciona las opciones para mostrar las categoriass en el clusterr.', 'rafax-cluster')}
                >
                    <div className="cluster grid-cols-3 style-4">
                        {categories.map(cat => (
                            <>
                                <a id={cat.id} {...(attributes.targetBlank ? { target: "_blank" } : {})} href={cat.link} className="post-grid-item vertical">

                                    <div className="content">
                                        <div className="title" >
                                            <h3>{cat.name + ' (' + cat.count + ')'} </h3>
                                        </div>
                                        <div className="description">
                                            <p>{cat.description}</p>
                                        </div>

                                    </div>

                                </a>
                            </>
                        ))}
                    </div>
                </Placeholder>
            </Fragment >
        );
    }),
    save: () => { return null; },
});

