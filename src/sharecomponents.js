const { ToolbarGroup, ToolbarDropdownMenu, SelectControl } = wp.components;
const { BlockControls } = wp.blockEditor;
import { grid } from '@wordpress/icons';

export function BlockStyles({ setAttributes }) {
    return (<BlockControls>
        <ToolbarGroup>
            <ToolbarDropdownMenu
                icon={grid}
                label="Estilo Grid"
                controls={[

                    {
                        title: 'Grid cols 2',

                        onClick: () => setAttributes({ styleGrid: 'grid-cols-2' }),
                    },
                    {
                        title: 'Grid cols 3',

                        onClick: () => setAttributes({ styleGrid: 'grid-cols-3' }),
                    },
                    {
                        title: 'Grid cols 4',

                        onClick: () => setAttributes({ styleGrid: 'grid-cols-4' }),
                    },

                ]}
            />

        </ToolbarGroup>
    </BlockControls>)
}

export function SelectorCats({ categories: cats, label: label, help: help, disabled: disabled, value: value, defaultItem: defaultItem, onChange: change }) {

    let categoryOptions = [];

    if (cats !== null) {

        categoryOptions = cats.map(category => ({
            label: category.name,
            value: category.id,
        }));
    }

    return (
        <SelectControl
            label={label}
            help={help}
            value={value}
            disabled={disabled}
            options={[defaultItem
                ,
                ...categoryOptions
                // Agrega más opciones de categorías según sea necesario
            ]}
            onChange={change}
        />

    );
}

export function setdefaultsAttrs(attributes=attributes,setAttributes=setAttributes){
    attributes.map((attr)=>{
        //setAttributes(attr.);

    });

}