import { createContext } from "react";

const TableContext = createContext({
    startMonth: 0,
    endMonth: 0,
    donations: []
});

export default TableContext;
