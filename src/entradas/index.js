const { registerBlockType } = wp.blocks;
const { withSelect } = wp.data;
const { Fragment } = wp.element;
const { __ } = wp.i18n;
const { PanelBody, Spinner, Placeholder, ToggleControl, TextControl, FormTokenField, Disabled, } = wp.components;
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
			default: false,
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
			default: 6,
		},
		styleGrid: {
			type: 'string',
			default: 'grid-cols-3'
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
						<FormTokenField
							label={__(
								'Mostrar solo estas entradas',
								'rafax-cluster'
							)}

							value={includePostsValue}
							suggestions={postNames}
							placeholder={ __(
								'Busca las entradas que quieras mostrar',
								'rafax-cluster'
							) }
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
						/>
						<TextControl
							label={__(
								'Numero de posts',
								'rafax-cluster'
							)}
							disabled={attributes.includePosts.length > 0}
							value={attributes.numberPosts}
							onChange={(value) => {

								setAttributes({ numberPosts: undefined === value ? -1 : value })
							}}
						/>
						<FormTokenField
							label={__(
								'Excluir entradas',
								'rafax-cluster'
							)}
							disabled={attributes.includePosts.length > 0}
							value={excludePostsValue}
							suggestions={postNames}
							placeholder={ __(
								'Busca las entradas a excluir',
								'rafax-cluster'
							) }
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
						/>
						<SelectorCats
							categories={categories}
							disabled={attributes.includePosts.length > 0}
							label={__('Categoría', 'rafax-cluster')}
							attributes={attributes}
							defaultItem={{ label: __('Todas las Categorías', 'rafax-cluster'), value: 'all' }}
							value={attributes.category}
							onChange={(value) => setAttributes({ category: value })} />
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

