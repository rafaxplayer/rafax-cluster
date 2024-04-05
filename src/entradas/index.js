const { registerBlockType } = wp.blocks;
const { withSelect } = wp.data;
const { Fragment } = wp.element;
const { __ } = wp.i18n;
const { PanelBody, Spinner, Placeholder, ToggleControl, TextControl, SelectControl, FormTokenField } = wp.components;
const { InspectorControls } = wp.blockEditor;
import { ReactComponent as Logo } from '../cluster.svg';


registerBlockType('rafax/cluster-entradas', {

	title: __('Rafax Cluster Entradas', 'rafax-cluster'),
	icon: { src: Logo },
	category: 'widgets',
	attributes: {
		showFeaturedImage: {
			type: 'boolean',
			default: false,
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
	},
	edit: withSelect((select, { clientId }) => {
		let selectCore = select( 'core' );
		const { getEditedPostAttribute } = select('core/editor');
		const postId = getEditedPostAttribute('id');
		const { category, numberPosts, excludePosts } = select('core/block-editor').getBlock(clientId).attributes;

		let queryArgs = {
			per_page: numberPosts,
			exclude: [postId, ...excludePosts],
			status: 'publish'
		};

		// Si se selecciona una categoría específica, añade el parámetro 'categories' a la consulta
		if (category !== 'all') {
			queryArgs.categories = category;
		}

		return {
			postId,
			categories: selectCore.getEntityRecords('taxonomy', 'category', { per_page: -1 }),
			selectedPosts: selectCore.getEntityRecords('postType', 'post', queryArgs),
			allPosts: selectCore.getEntityRecords('postType', 'post', { per_page: -1 }),
		};
	})(({ postId,categories, selectedPosts, allPosts, attributes, setAttributes }) => {

		if (!categories || !selectedPosts || !allPosts) {
			return (
				<div className="rafax-cluster-spinner">
					<Spinner />
					{__('Cargando...', 'rafax-cluster')}
				</div>
			);
		}
		// Construir opciones para el selector de post
		const categoryOptions = categories.map(category => ({
			label: category.name,
			value: category.id,
		}));

		let postNames = [];
		let postsFieldValue = [];
		if (allPosts !== null) {
			postNames = allPosts.map((post) => post.title.raw);

			postsFieldValue = attributes.excludePosts.map((postId) => {
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
				<InspectorControls>
					<PanelBody title={__('Opciones', 'rafax-cluster')} initialOpen={true}>
						<ToggleControl
							label={__('Mostrar Imagen Destacada', 'rafax-cluster')}
							checked={attributes.showFeaturedImage}
							onChange={(value) => setAttributes({ showFeaturedImage: value })}
						/>
						<TextControl
							label={__(
								'Numero de posts',
								'rafax-cluster'
							)}
							value={attributes.numberPosts}
							onChange={(value) => {

								setAttributes({ numberPosts: undefined === value ? '' : value })
							}}
						/>
						<FormTokenField
							label={__(
								'Excluir entradas',
								'rafax-cluster'
							)}
							help={__(
								'Incluye la id de la entradas a excluir separadas por coma',
								'rafax-cluster'
							)}
							value={postsFieldValue}
							suggestions={postNames}
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
								setAttributes({ excludePosts:selectedPostsArray });
							}}
						/>
						<SelectControl
							label={__('Categoría', 'rafax-cluster')}
							value={attributes.category}
							options={[
								{ label: __('Todas las Categorías', 'rafax-cluster'), value: 'all' },
								...categoryOptions
								// Agrega más opciones de categorías según sea necesario
							]}
							onChange={(value) => setAttributes({ category: value })}
						/>
					</PanelBody>
				</InspectorControls>
				<Placeholder
					icon="admin-post"
					label={__('Rafax Cluster', 'rafax-cluster')}
					instructions={__('Selecciona las opciones para mostrar las entradas en el clusterr.', 'rafax-cluster')}
				>
					
					<div className="cluster grid-cols-3 style-4">
						{selectedPosts.map(post => (
							<>
								<a id={post.id} href={post.guid.rendered} className="post-grid-item vertical">
									<div className="thumb">
										{attributes.showFeaturedImage && <img src={post.image_post} alt={post.title.rendered} />}

									</div>
									<div className="content">
										<div className="title" >
											<h3>{post.title.rendered}</h3>
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

