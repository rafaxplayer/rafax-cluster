const { registerBlockType } = wp.blocks;
const { InspectorControls, MediaUpload, BlockControls } = wp.blockEditor;
const { __ } = wp.i18n;
const { Fragment, useEffect } = wp.element;
const { PanelBody, Disabled, RangeControl, Button, ButtonGroup, Placeholder, ToggleControl, ToolbarGroup, ToolbarDropdownMenu } = wp.components;
import { __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components'
import { useState } from 'react';
import ServerSideRender from '@wordpress/server-side-render';
import { blockMeta, seen, plus, trash } from '@wordpress/icons';

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
        plantilla: {
            type: 'string',
            default: '1'

        }
    },

    edit: ({ attributes, setAttributes }) => {

        const { csvFile, numberItems, rand } = attributes;

        useEffect(() => {
            
            console.log('Block added');

            return () => {
                console.log('Block deleted');
                setAttributes({ removeCsv: csvFile.id, csvFile: '' })

            }
        }, []);

        const [isOpen, setIsOpen] = useState(false);

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
                    <PanelBody title={__('Opciones cluster directorio', 'rafax-cluster')} initialOpen={true}>

                        <MediaUpload
                            onSelect={(csv) => {
                                
                                setAttributes({ csvFile: csv, removeCsv: '' });
                            }}
                            allowedTypes={['text/csv']}
                            value={csvFile}
                            render={({ open }) => (
                                <>

                                    <Button icon={plus} variant="primary" onClick={open}>
                                        {attributes.csvFile
                                            ? 'Upload CSV'
                                            : 'Upload new file'}
                                    </Button>
                                    {attributes.csvFile && <Button icon={trash} className="rfc-danger" variant="secondary" onClick={() => { setIsOpen(true); }}>
                                        Remove csv
                                    </Button>}
                                    <p><b>
                                        {csvFile
                                            ? 'Archivo: ' + csvFile.filename
                                            : ''}
                                    </b></p>
                                </>
                            )}
                        />
                        <ConfirmDialog
                            isOpen={isOpen}
                            onConfirm={() => {
                                setAttributes({ removeCsv: csvFile.id, csvFile: '' })
                                setIsOpen(false);
                            }}
                            onCancel={() => {
                                setIsOpen(false);
                            }}
                        >
                            <p> Se eliminara el archivo csv {csvFile.filename}</p>

                        </ConfirmDialog>
                        <ToggleControl
                            label={__('Orden aleatorio', 'rafax-cluster')}
                            checked={rand}
                            onChange={(value) => setAttributes({ rand: value })}
                        />
                        <RangeControl
                            label={__(
                                'Numero de fichas',
                                'rafax-cluster'
                            )}
                            value={parseInt(numberItems)}
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

