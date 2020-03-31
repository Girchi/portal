import React, { useState, useEffect, useContext } from "react";
import AppContext from "../AppContext";
import Axios from "axios";

const NotificationBell = () => {
    const [appInfo, setAppInfo] = useContext(AppContext);
    const [unredCount, setUnredCount] = useState(appInfo.unreadCount);

    useEffect(() => {
        Axios.get(
            "http://notifications.girchi.docker.localhost/notifications/user/unread-count",
            {
                withCredentials: true
            }
        ).then(
            res => {
                console.log(appInfo.unreadCount);
                setAppInfo({ ...appInfo, unreadCount: res.data.count });
            },
            err => console.log(err)
        );
    }, [appInfo, setAppInfo]);

    return (
        <div className="notifications__icon">
            <img src="http://girchi.docker.localhost/themes/custom/girchi/images/Bell.svg" />
            <span>{unredCount}</span>
        </div>
    );
};

export default NotificationBell;
