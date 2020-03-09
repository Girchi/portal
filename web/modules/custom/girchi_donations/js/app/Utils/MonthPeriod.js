const CalculateMonthPeriod = (startMonth, endMonth) => {
    const months = [
        {
            Header: "იანვარი",
            accessor: "donations.01"
        },
        {
            Header: "თებერვალი",
            accessor: "donations.02"
        },
        {
            Header: "მარტი",
            accessor: "donations.03"
        },
        {
            Header: "აპრილი",
            accessor: "donations.04"
        },
        {
            Header: "მაისი",
            accessor: "donations.05"
        },
        {
            Header: "ივნისი",
            accessor: "donations.06"
        },
        {
            Header: "ივლისი",
            accessor: "donations.07"
        },
        {
            Header: "აგვისტო",
            accessor: "donations.08"
        },
        {
            Header: "სექტემბერი",
            accessor: "donations.09"
        },
        {
            Header: "ოქტომბერი",
            accessor: "donations.10"
        },
        {
            Header: "ნოემბერი",
            accessor: "donations.11"
        },
        {
            Header: "დეკემბერი",
            accessor: "donations.12"
        }
    ];
    return months.slice(startMonth, endMonth + 1);
};

export default CalculateMonthPeriod;
