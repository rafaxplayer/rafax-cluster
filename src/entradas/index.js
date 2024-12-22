const { registerBlockType } = wp.blocks;
const { withSelect } = wp.data;
const { Fragment } = wp.element;
const { __ } = wp.i18n;
const { PanelBody, Spinner, Placeholder, ToggleControl, SelectControl, FormTokenField, Disabled, RangeControl } = wp.components
const { InspectorControls } = wp.blockEditor;

import { ToolbarOptions, SelectorCats, Loading } from '../sharecomponents';
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
		contentType: {
			type: 'string',
			default: 'post',
		},
		parentPage:{
			type: 'number', // Almacena el ID de la página padre
			default: 0,
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
		},

	},
	edit: withSelect((select, { clientId }) => {

		const selectCore = select('core');
		const selectEditor = select('core/block-editor');

		const { attributes } = selectEditor.getBlock(clientId);

		const contentType = attributes.contentType || 'post';
		return {
			// todas categorias o posts para el selector FormTokenField
			categories: selectCore.getEntityRecords('taxonomy', 'category', { per_page: -1 }),// Categorias
			allPosts: selectCore.getEntityRecords('postType', contentType, { per_page: attributes.numberPosts }),// Posts o Paginas 
		};

	})(({ categories, allPosts, attributes, setAttributes }) => {
		//resetear attributos
		const resetAttributes = () => {
			setAttributes({
				includePosts: [],
				excludePosts: [],
				category: 'all',
				numberPosts: '100',
			});
		};
		// funcion para limpiar los titulos de caracteres rarros y html y espacios
		const normalizeTitle = (title) => title
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '') // Elimina tildes y otros caracteres de acentuación
			.replace(/<[^>]+>/g, '') // Elimina etiquetas HTML
			.trim();

		const isLoading = !categories || !allPosts;

		const { excludePosts, includePosts, showFeaturedImage, typeSelect, numberPosts, order, orderBy, category,parentPage, contentType } = attributes;

		const handleshowImage = (value) => setAttributes({ showFeaturedImage: value });
		const handlenumberPosts = (value) => setAttributes({ numberPosts: String(value) });
		const handleCategory = (value) => setAttributes({ category: value });
		const handleorderBy = (value) => setAttributes({ orderBy: value });
		const handleOrder = (value) => setAttributes({ order: value });


		let postNames = [];
		let excludePostsValue = [];
		let includePostsValue = [];

		let postsById = {};
		if (allPosts !== null) {

			postNames = allPosts?.map((post) => normalizeTitle(post.title.raw));

			//Convertir allposts en un obejto de ids para facilitar la busqueda
			postsById = allPosts.reduce((acc, post) => {
				acc[post.id] = post;
				return acc;
			}, {});

			// Rellenar el selector de esxcluir posts
			excludePostsValue = excludePosts?.map((postId) => {
				let wantedPost = postsById[postId]; // Buscar el post directamente por ID
				if (!wantedPost) {
					return false;
				}
				return normalizeTitle(wantedPost.title.raw);
			});

			// Rellenar el selector de incluir posts
			includePostsValue = includePosts?.map((postId) => {
				let wantedPost = postsById[postId]; // Buscar el post directamente por ID
				if (!wantedPost) {
					return false;
				}
				return normalizeTitle(wantedPost.title.raw);
			});

		}

		return (
			<Fragment>
				<ToolbarOptions setAttributes={setAttributes} />
				<InspectorControls>
					<PanelBody title={__('Opciones', 'rafax-cluster')} initialOpen={true}>
						<ToggleControl
							label={__('Mostrar Imagen Destacada', 'rafax-cluster')}
							checked={showFeaturedImage}
							onChange={handleshowImage}
							__nextHasNoMarginBottom={true}
						/>
						{<SelectControl
							label={__('Tipo de contenido', 'rafax-cluster')}
							value={contentType}
							options={[
								{ label: __('Entradas', 'rafax-cluster'), value: 'post' },
								{ label: __('Páginas', 'rafax-cluster'), value: 'page' },
							]}
							onChange={(value) => {

								setAttributes({ contentType: value })
								resetAttributes()
								if (value === 'page' && typeSelect === '2') {
									setAttributes({ typeSelect: '1' });
								}
							}
							}
						/>};
						{contentType === 'page' && <SelectControl
							label={__('Seleccionar página padre', 'rafax-cluster')}
							value={parentPage}
							options={[
								{ label: __('Ninguna', 'rafax-cluster'), value: 0 }, // Opción para no filtrar
								...(allPosts ? allPosts.map((page) => ({
									label: page.title.rendered,
									value: page.id,
								})) : []),
							]}
							onChange={(value) => setAttributes({ parentPage: parseInt(value, 10) })}
							__nextHasNoMarginBottom={true}
						/>}

						<SelectControl
							label={__('Tipo de seleccion', 'rafax-cluster')}
							value={typeSelect}
							options={[
								{ label: 'Últimas entradas o paginas', value: '1' },
								...(contentType === 'post' ? [{ label: 'Entradas de una categoría', value: '2' }] : []),
								{ label: 'Elegir entradas o paginas', value: '3' }]}
							onChange={(value) => {
								resetAttributes()
								setAttributes({ typeSelect: value })

							}}
							__nextHasNoMarginBottom={true}
						/>

						{typeSelect === '3' && <FormTokenField
							label={__(
								'Mostrar solo estas entradas o paginas',
								'rafax-cluster'
							)}

							value={includePostsValue}
							suggestions={postNames}
							placeholder={__(
								'Busca las entradas que quieras mostrar',
								'rafax-cluster'
							)}
							onChange={(selectedPosts) => {

								const selectedPostsArray = selectedPosts.map((postName) => {
									const matchingPost = allPosts.find((post) => {
										return normalizeTitle(post.title.raw) === normalizeTitle(postName);
									});
									return matchingPost ? matchingPost.id : null; // Retorna el ID o null.
								}).filter(Boolean); // Filtra valores nulos.

								setAttributes({ includePosts: selectedPostsArray });
							}}
							__nextHasNoMarginBottom={true}
						/>}

						{(typeSelect === '1' || typeSelect === '2') && <RangeControl
							label={__(
								'Numero de posts',
								'rafax-cluster'
							)}
							value={parseInt(numberPosts)}
							onChange={handlenumberPosts}
							min={1}
							max={100}
							__nextHasNoMarginBottom={true}

						/>}

						{attributes.typeSelect === '1' && <FormTokenField
							label={__(
								'Excluir entradas o paginas',
								'rafax-cluster'
							)}
							value={excludePostsValue}
							suggestions={postNames}
							placeholder={__(
								'Busca las entradas a excluir',
								'rafax-cluster'
							)}
							onChange={(selectedPosts) => {

								const selectedPostsArray = selectedPosts.map((postName) => {
									const matchingPost = allPosts.find((post) => {
										return normalizeTitle(post.title.raw) === normalizeTitle(postName);
									});
									return matchingPost ? matchingPost.id : null; // Retorna el ID o null.
								}).filter(Boolean); // Filtra valores nulos.

								setAttributes({ excludePosts: selectedPostsArray });
							}}
							__nextHasNoMarginBottom={true}
						/>}


						{typeSelect === '2' && contentType === 'post' && <SelectorCats// selector de categorias
							categories={categories}
							label={__('Categoría', 'rafax-cluster')}
							attributes={attributes}
							defaultItem={{ label: __('Todas las Categorías', 'rafax-cluster'), value: 'all' }}
							value={category}
							onChange={handleCategory}

						/>}

						<SelectControl
							label={__('Ordenar por', 'rafax-cluster')}
							value={orderBy}
							options={[{ label: 'Título', value: 'title' }, { label: 'Fecha', value: 'date' }, { label: 'Aleatorio', value: 'rand' }]}
							onChange={handleorderBy}
							__nextHasNoMarginBottom={true}
						/>
						<SelectControl
							label={__('Orden', 'rafax-cluster')}
							value={order}
							options={[{ label: 'Ascendente', value: 'ASC' }, { label: 'Descendente', value: 'DESC' }]}
							onChange={handleOrder}
							__nextHasNoMarginBottom={true}
						/>
					</PanelBody>
				</InspectorControls>
				<Placeholder
					icon="feedback"
					label={__('Rafax Cluster de Entradas', 'rafax-cluster')}
					instructions={__('Selecciona las opciones para mostrar las entradas en el cluster.', 'rafax-cluster')}
				>
					{isLoading && <Loading label={__('Cargando...', 'rafax-cluster')} />}
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

