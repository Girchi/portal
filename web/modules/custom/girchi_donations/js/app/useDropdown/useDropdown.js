import React, { useState } from "react";
import Select from "material-ui-core/Select";
import MenuItem from "material-ui-core/MenuItem";
import InputLabel from "material-ui-core/InputLabel";
import "./useDropdown.css";

const useDropdown = (label, defaultState, options) => {
    const [state, setState] = useState(defaultState);
    const id = `use-dropdown${label.replace(" ").toLowerCase()}`;
    const Dropdown = () => {
        return (
            <div className="dropdown-wrapper">
                <InputLabel htmlFor={id}>{label}</InputLabel>
                <Select
                    id={id}
                    value={state}
                    onChange={e => setState(e.target.value)}
                    onBlur={e => setState(e.tartget.value)}
                    disabled={options.length === 0}
                >
                    {options.map(item => (
                        <MenuItem value={item.toLowerCase()} key={item}>
                            {item}
                        </MenuItem>
                    ))}
                </Select>
            </div>
        );
    };
    return [state, Dropdown, setState];
};

export default useDropdown;
