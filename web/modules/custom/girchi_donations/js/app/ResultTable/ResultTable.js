import React, { useContext, useEffect } from "react";

import CssBaseline from "material-ui-core/CssBaseline";
import MaUTable from "material-ui-core/Table";
import TableBody from "material-ui-core/TableBody";
import TableCell from "material-ui-core/TableCell";
import TableHead from "material-ui-core/TableHead";
import TableRow from "material-ui-core/TableRow";
import { useTable } from "react-table";
import TableContext from "../TableContext";
import CalculateMonthPeriod from "../Utils/MonthPeriod";

import makeData from "./makeData";

function Table({ columns, data }) {
    // Use the state and functions returned from useTable to build your UI
    const { getTableProps, headerGroups, rows, prepareRow } = useTable({
        columns,
        data
    });
    // Render the UI for your table
    return (
        <MaUTable {...getTableProps()}>
            <TableHead>
                {headerGroups.map(headerGroup => (
                    <TableRow {...headerGroup.getHeaderGroupProps()}>
                        {headerGroup.headers.map(column => (
                            <TableCell {...column.getHeaderProps()}>
                                {column.render("Header")}
                            </TableCell>
                        ))}
                    </TableRow>
                ))}
            </TableHead>
            <TableBody>
                {rows.map((row, i) => {
                    prepareRow(row);
                    return (
                        <TableRow {...row.getRowProps()}>
                            {row.cells.map(cell => {
                                return (
                                    <TableCell {...cell.getCellProps()}>
                                        {cell.render("Cell")}
                                    </TableCell>
                                );
                            })}
                        </TableRow>
                    );
                })}
            </TableBody>
        </MaUTable>
    );
}

function ResultTable() {
    const [{ startMonth, endMonth, donations }, setTableInfo] = useContext(
        TableContext
    );
    const months = CalculateMonthPeriod(startMonth, endMonth);

    const columns = [
        {
            Header: "მომხმარებელი",
            accessor: "firstName"
        },
        {
            Header: "თვეები",
            columns: months
        }
    ];

    return (
        <div>
            <CssBaseline />
            <Table columns={columns} data={donations} />
        </div>
    );
}

export default ResultTable;
