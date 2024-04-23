const { registerBlockType } = wp.blocks;
const { InspectorControls, MediaUpload } = wp.blockEditor;
const { __ } = wp.i18n;
const { Fragment } = wp.element;
const { useEffect } = wp.element;
const { BlockControls } = wp.blockEditor;
const { PanelBody, Disabled, RangeControl, Placeholder, ToggleControl,ToolbarGroup,ToolbarDropdownMenu } = wp.components;
import { seen } from '@wordpress/icons';

import ServerSideRender from '@wordpress/server-side-render';
import { blockMeta } from '@wordpress/icons';

registerBlockType('rafax/directorist-csv', {
    title: __('Rafax directorio', 'rafax-cluster'),
    icon: { src: blockMeta },
    category: 'rafax-blocks',
    attributes: {
        csvFile: {
            type: 'object',
            default: ''
        },
        removeCsv: {
            type: 'string',
            default: ""
        },
        numberItems: {
            type: 'string',
            default: '10'
        },
        rand: {
            type: 'boolean',
            default: false
        },
        plantilla:{
            type: 'string',
            default: '1'

        }
    },

    edit: ({ attributes, setAttributes }) => {
        useEffect(() => {
            console.log('Block added');

            return () => {
                console.log('Block deleted');
                //setAttributes({ removeCsv: attributes.csvFile.id, csvFile: '' })

            }
        }, []);


        const removeCsv = () => {

            if (confirm(__(`Se eliminara el archivo csv ${attributes.csvFile.filename}`, 'rafax-cluster'))) {

                setAttributes({ removeCsv: attributes.csvFile.id, csvFile: '' })
            }
        }

        return (
            <Fragment>
                <BlockControls>
                    <ToolbarGroup>
                        <ToolbarDropdownMenu
                            icon={seen}
                            label="Plantilla"
                            controls={[

                                {
                                    title: 'Plantilla 1',

                                    onClick: () => setAttributes({ plantilla: '1' }),
                                },
                                {
                                    title: 'Plantilla 2',

                                    onClick: () => setAttributes({ plantilla: '2' }),
                                },
                                

                            ]}
                        />

                    </ToolbarGroup>
                </BlockControls>
                <InspectorControls>
                    <PanelBody title={__('Opciones cluster categorías', 'rafax-cluster')} initialOpen={true}>

                        <MediaUpload
                            onSelect={(csv) => {
                                console.log(csv);
                                setAttributes({ csvFile: csv, removeCsv: '' });
                            }}
                            allowedTypes={['text/csv']}
                            value={attributes.csvFile}
                            render={({ open }) => (
                                <>
                                    <button onClick={open}>
                                        {attributes.csvFile
                                            ? 'Upload'
                                            : 'Upload new file'}
                                    </button>
                                    {attributes.csvFile && <button onClick={removeCsv}>
                                        Remove csv
                                    </button>
                                    }
                                    <p>
                                        {attributes.csvFile
                                            ? attributes.csvFile.filename
                                            : ''}
                                    </p>
                                </>
                            )}
                        />
                        <ToggleControl
                            label={__('Orden aleatorio', 'rafax-cluster')}
                            checked={attributes.rand}
                            onChange={(value) => setAttributes({ rand: value })}
                        />
                        <RangeControl
                            label={__(
                                'Numero de fichas',
                                'rafax-cluster'
                            )}
                            value={parseInt(attributes.numberItems)}
                            onChange={(value) => setAttributes({ numberItems: String(value) })}
                            min={1}
                            max={100}

                        />

                    </PanelBody>
                </InspectorControls>
                <Placeholder
                    icon="category"
                    label={__('Rafax Cluster Directorio', 'rafax-cluster')}
                    instructions={__('Selecciona las opciones para mostrar el directorio.', 'rafax-cluster')}
                >
                    <Disabled>
                        <ServerSideRender
                            block={'rafax/directorist-csv'}
                            skipBlockSupportAttributes
                            attributes={attributes}
                        />
                    </Disabled>
                </Placeholder>
            </Fragment >
        );

    },
    save: () => { return null; },
});

