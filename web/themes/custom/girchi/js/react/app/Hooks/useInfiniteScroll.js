import { useState, useEffect } from "react";

const useInfiniteScroll = callback => {
    const [isFetching, setIsFetching] = useState(false);

    useEffect(() => {
        window.addEventListener("scroll", handleScroll);
        return () => window.removeEventListener("scroll", handleScroll);
    }, []);

    useEffect(() => {
        if (!isFetching) return;
        callback();
    }, [isFetching]);

    function handleScroll() {
        const windowHeight =
            "innerHeight" in window
                ? window.innerHeight
                : document.documentElement.offsetHeight;
        const body = document.body;
        const html = document.documentElement;
        const docHeight = Math.max(
            body.scrollHeight,
            body.offsetHeight,
            html.clientHeight,
            html.scrollHeight,
            html.offsetHeight
        );
        const windowBottom = windowHeight + window.pageYOffset;

        if (windowBottom >= docHeight) {
            setIsFetching(true);
        }
    }

    return [isFetching, setIsFetching];
};

export default useInfiniteScroll;
