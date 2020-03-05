import React, { useState } from "react";
import Filters from "./Filters/Filters";
import ResultTable from "./ResultTable/ResultTable";
import TableContext from "./TableContext";
const App = () => {
    const currentMonth = new Date().getMonth();
    const tableHook = useState({
        startMonth: currentMonth,
        endMonth: currentMonth,
        donations: []
    });
    return (
        <TableContext.Provider value={tableHook}>
            <div>
                <Filters />
                <ResultTable />
            </div>
        </TableContext.Provider>
    );
};

export default App;
