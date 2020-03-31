import React, { useState, useEffect, useContext } from "react";
import Notification from "../Notification/Notification";
import Axios from "axios";
import AppContext from "../AppContext";

const NotificationWrapper = ({ notifications }) => {
    const [appInfo, setAppInfo] = useContext(AppContext);

    const ENDPOINT = "http://notifications.girchi.docker.localhost/";

    async function readNotification(_id) {
        Axios(`${ENDPOINT}notifications/${_id}/read`, {
            method: "post",
            withCredentials: true
        }).then(
            res => {
                console.log(res);
            },
            err => console.log(err)
        );
    }

    useEffect(() => {
        Axios.get(
            "http://notifications.girchi.docker.localhost/notifications/user/unread-count",
            {
                withCredentials: true
            }
        ).then(
            res => {
                setAppInfo({ ...appInfo, unreadCount: res.data.count });
            },
            err => console.log(err)
        );
    }, []);

    return (
        <div>
            <div className="notifications__icon">
                <img src="http://girchi.docker.localhost/themes/custom/girchi/images/Bell.svg" />
                <span>{appInfo.unreadCount}</span>
            </div>
            <div className="notifications__notifi-box">
                <div className="notifications__notifi-box__triangle"></div>

                {notifications.length > 0 ? (
                    notifications.map(notification => (
                        <Notification
                            notification={notification}
                            readNotification={readNotification}
                            key={notification._id}
                        />
                    ))
                ) : (
                    <div className="notifications__notifi-box__item">
                        <a>
                            <div className="notifications__notifi-box__item__text">
                                <h4>თქვენ არ გაქვთ შეტყობინება</h4>
                            </div>
                        </a>
                    </div>
                )}
                <div className="notifications__notifi-box__item">
                    <a
                        className="d-flex justify-content-center align-items-center notifications__notifi-box__item__text"
                        href="#"
                    >
                        მეტის ნახვა{" "}
                        <span>
                            <i className="ml-3 icon-arrow-right2"></i>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    );
};

export default NotificationWrapper;
