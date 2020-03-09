import React, { useState, useContext, useEffect } from "react";
import DatePicker from "react-datepicker";
import { CssBaseline } from "material-ui-core";
import Button from "material-ui-core/Button";
import useDropdown from "../useDropdown/useDropdown";
import TableContext from "../TableContext";
import download from "../Utils/download";
import "./Filters.css";
import "react-datepicker/dist/react-datepicker.css";

const Filters = ({ currentMonth }) => {
    const [startDate, setStartDate] = useState(new Date());
    const [endDate, setEndDate] = useState(startDate);
    const [showButton, setShowButton] = useState(false);

    const [
        donationSource,
        SourceDropdown,
        setDonationSource
    ] = useDropdown("Donation source", "tbc", ["TBC", "PayPal"]);
    const [tableInfo, setTableInfo] = useContext(TableContext);
    async function getDonations(type) {
        const year = startDate.getFullYear();
        const startMonth = startDate.getMonth() + 1;
        const endMonth = endDate.getMonth() + 1;
        const query = `/donations/export/resource?year=${year}&months=${startMonth}:${endMonth}&donation_source=${donationSource}`;
        if (type === "excel") {
            fetch(query, {
                headers: {
                    accept:
                        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                }
            })
                .then(res => res.blob())
                .then(result =>
                    download(
                        result,
                        `donations-${year}-${startMonth}-${endMonth}.xlsx`
                    )
                );
        } else if (type === "json") {
            fetch(query)
                .then(res => res.json())
                .then(result => {
                    setTableInfo({ ...tableInfo, donations: result });
                    setShowButton(true);
                });
        }
    }

    useEffect(() => {
        getDonations("json");
    }, [
        endDate,
        setEndDate,
        startDate,
        setStartDate,
        donationSource,
        setDonationSource
    ]);

    return (
        <div>
            <CssBaseline />
            <div>
                <DatePicker
                    selected={startDate}
                    onChange={date => {
                        setStartDate(date);
                        if (
                            endDate < date ||
                            endDate.getFullYear() != date.getFullYear()
                        ) {
                            setEndDate(date);
                        }
                        setTableInfo({
                            ...tableInfo,
                            startMonth: date.getMonth(),
                            endMonth:
                                endDate < date
                                    ? date.getMonth()
                                    : endDate.getMonth()
                        });
                    }}
                    selectsStart
                    startDate={startDate}
                    endDate={endDate}
                    dateFormat="MMMM"
                    showMonthYearPicker
                    className="date-picker"
                />
            </div>
            <div>
                <DatePicker
                    selected={endDate}
                    onChange={date => {
                        setEndDate(date);
                        setTableInfo({
                            ...tableInfo,
                            endMonth: date.getMonth()
                        });
                    }}
                    selectsEnd
                    startDate={startDate}
                    endDate={endDate}
                    minDate={startDate}
                    maxDate={new Date(startDate.getFullYear(), 11, 31)}
                    dateFormat="MMMM"
                    showMonthYearPicker
                    className="date-picker"
                />
            </div>
            <div>
                <SourceDropdown />
            </div>
            <Button
                variant="contained"
                color="primary"
                type="submit"
                className="filter-button"
                disabled={showButton ? false : true}
                onClick={() => getDonations("excel")}
            >
                Download Execl
            </Button>
        </div>
    );
};

export default Filters;
