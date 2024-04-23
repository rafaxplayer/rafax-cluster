const { registerBlockType } = wp.blocks;
const { withSelect } = wp.data;
const { Fragment } = wp.element;
const { __ } = wp.i18n;
const { PanelBody, Spinner, Placeholder, ToggleControl, SelectControl, FormTokenField, Disabled, RangeControl } = wp.components;
const { InspectorControls, useBlockProps } = wp.blockEditor;

import { BlockStyles, SelectorCats } from '../sharecomponents';
import { post } from '@wordpress/icons';
import ServerSideRender from '@wordpress/server-side-render';



registerBlockType('rafax/cluster-entradas', {

	title: __('Rafax Cluster Entradas', 'rafax-cluster'),
	icon: { src: post },
	category: 'rafax-blocks',
	attributes: {
		showFeaturedImage: {
			type: 'boolean',
			default: true,
		},
		includePosts: {
			type: 'array',
			default: [],
		},
		excludePosts: {
			type: 'array',
			default: [],
		},
		category: {
			type: 'string',
			default: 'all',
		},
		numberPosts: {
			type: 'string',
			default: 20,
		},
		orderBy: {
			type: 'string',
			default: 'date'

		},
		order: {
			type: 'string',
			default: 'DESC'

		},
		styleGrid: {
			type: 'string',
			default: 'grid-cols-3'
		},
		typeSelect: {
			type: 'string',
			default: '1'
		}
	},
	edit: withSelect((select, { clientId }) => {

		let selectCore = select('core');

		const { attributes } = select('core/block-editor').getBlock(clientId);

		return {
			categories: selectCore.getEntityRecords('taxonomy', 'category', { per_page: -1 }),
			allPosts: selectCore.getEntityRecords('postType', 'post', { per_page: attributes.numberPosts }),
		};
	})(({ categories, allPosts, attributes, setAttributes }) => {

		const resetAttributes = () => {
			setAttributes({
				includePosts: [],
				excludePosts: [],
				category: 'all',
				numberPosts: '100',

			});
		};

		if (!categories || !allPosts) {
			return (
				<div className="rafax-cluster-spinner">
					<Spinner />
					{__('Cargando...', 'rafax-cluster')}
				</div>
			);
		}

		let postNames = [];
		let excludePostsValue = [];
		let includePostsValue = [];

		if (allPosts !== null) {
			postNames = allPosts.map((post) => post.title.raw);

			// rellenar el selector de excluir posts
			excludePostsValue = attributes.excludePosts.map((postId) => {
				let wantedPost = allPosts.find((post) => {
					return post.id === postId;
				});
				if (wantedPost === undefined || !wantedPost) {
					return false;
				}
				return wantedPost.title.raw;
			});

			// rellenar el selector de incluir posts
			includePostsValue = attributes.includePosts.map((postId) => {
				let wantedPost = allPosts.find((post) => {
					return post.id === postId;
				});
				if (wantedPost === undefined || !wantedPost) {
					return false;
				}
				return wantedPost.title.raw;
			});
		}
		return (
			<Fragment>
				<BlockStyles setAttributes={setAttributes} />
				<InspectorControls>
					<PanelBody title={__('Opciones', 'rafax-cluster')} initialOpen={true}>
						<ToggleControl
							label={__('Mostrar Imagen Destacada', 'rafax-cluster')}
							checked={attributes.showFeaturedImage}
							onChange={(value) => setAttributes({ showFeaturedImage: value })}
						/>
						<SelectControl
							label={__('Tipo de seleccion', 'rafax-cluster')}
							value={attributes.typeSelect}
							options={[{ label: 'Últimas entradas', value: '1' }, { label: 'Entradas de una categoría', value: '2' }, { label: 'Elegir entradas', value: '3' }]}
							onChange={(value) => {

								setAttributes({ typeSelect: value })
								resetAttributes()
							}}
						/>

						{attributes.typeSelect === '3' && <FormTokenField
							label={__(
								'Mostrar solo estas entradas',
								'rafax-cluster'
							)}

							value={includePostsValue}
							suggestions={postNames}
							placeholder={__(
								'Busca las entradas que quieras mostrar',
								'rafax-cluster'
							)}
							onChange={(selectedPosts) => {
								// Build array of selected posts.
								let selectedPostsArray = [];
								selectedPosts.map(
									(postName) => {
										const matchingPost = allPosts.find((post) => {
											return post.title.raw === postName;
										});
										if (matchingPost !== undefined) {
											selectedPostsArray.push(matchingPost.id);
										}
									}
								)
								setAttributes({ includePosts: selectedPostsArray });
							}}
						/>}
						{(attributes.typeSelect === '1' || attributes.typeSelect === '2') && <RangeControl
							label={__(
								'Numero de posts',
								'rafax-cluster'
							)}
							value={parseInt(attributes.numberPosts)}
							onChange={(value) => setAttributes({ numberPosts: String(value) })}
							min={1}
							max={100}

						/>}

						{attributes.typeSelect === '1' && <FormTokenField
							label={__(
								'Excluir entradas',
								'rafax-cluster'
							)}
							value={excludePostsValue}
							suggestions={postNames}
							placeholder={__(
								'Busca las entradas a excluir',
								'rafax-cluster'
							)}
							onChange={(selectedPosts) => {
								// Build array of selected posts.
								let selectedPostsArray = [];
								selectedPosts.map((postName) => {
									const matchingPost = allPosts.find((post) => {
										return post.title.raw === postName;
									});

									if (matchingPost !== undefined) {
										selectedPostsArray.push(matchingPost.id);
									}
								}
								)
								setAttributes({ excludePosts: selectedPostsArray });
							}}
						/>}
						{attributes.typeSelect === '2' && <SelectorCats
							categories={categories}
														label={__('Categoría', 'rafax-cluster')}
							attributes={attributes}
							defaultItem={{ label: __('Todas las Categorías', 'rafax-cluster'), value: 'all' }}
							value={attributes.category}
							onChange={(value) => setAttributes({ category: value })} />}

						<SelectControl
							label={__('Ordenar por', 'rafax-cluster')}
							value={attributes.orderBy}
							options={[{ label: 'Título', value: 'title' }, { label: 'Fecha', value: 'date' }, { label: 'Aleatorio', value: 'rand' }]}
							onChange={(value) => setAttributes({ orderBy: value })}
						/>
						<SelectControl
							label={__('Orden', 'rafax-cluster')}
							value={attributes.order}
							options={[{ label: 'Ascendente', value: 'ASC' }, { label: 'Descendente', value: 'DESC' }]}
							onChange={(value) => setAttributes({ order: value })}
						/>
					</PanelBody>
				</InspectorControls>
				<Placeholder
					icon="feedback"
					label={__('Rafax Cluster de Entradas', 'rafax-cluster')}
					instructions={__('Selecciona las opciones para mostrar las entradas en el clusterr.', 'rafax-cluster')}
				>
					<Disabled>
						<ServerSideRender
							block={'rafax/cluster-entradas'}
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

