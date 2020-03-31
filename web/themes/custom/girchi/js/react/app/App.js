import React, { useState, useEffect } from "react";
import NotificationFP from "./NotificationFP/NotificationFP";
import { generateJwtIfExpired } from "./Utils/Utils";
import Axios from "axios";

const App = ({ socket, accessToken }) => {
    const [notifications, setNotifications] = useState([]);
    const [socketNotification, setSocketNotification] = useState(0);
    const [page, setPage] = useState(1);
    const ENDPOINT = "http://notifications.girchi.docker.localhost/";

    const handleScroll = () => {
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
            setPage(currentPage => currentPage + 1);
        }
    };
    const getNotifications = () => {
        Axios.get(
            `http://notifications.girchi.docker.localhost/notifications/user/?page=${page}`,
            {
                withCredentials: true
            }
        ).then(
            res => {
                setNotifications(notifications.concat(res.data.notifications));
            },
            err => console.log(err)
        );
    };
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
        window.addEventListener("scroll", handleScroll);
        socket.on("notification added", notification => {
            setNotifications(currentNotifications => [
                notification,
                ...currentNotifications
            ]);
        });
    }, []);
    useEffect(() => {
        getNotifications();
    }, [page]);

    return (
        <div className="card-body p-0">
            {notifications.length > 0 ? (
                notifications.map(notification => (
                    <NotificationFP
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
        </div>
    );
};

export default App;
