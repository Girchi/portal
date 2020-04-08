import React, { useState, useEffect, useContext } from "react";
import NotificationFP from "./NotificationFP/NotificationFP";
import Axios from "axios";
import useInfiniteScroll from "./Hooks/useInfiniteScroll";

const App = ({ socket, accessToken }) => {
    const [notifications, setNotifications] = useState([]);
    const [fetch, setFetch] = useInfiniteScroll(getNotifications);
    const [page, setPage] = useState(1);
    const ENDPOINT = process.env.REACT_APP_ENDPOINT;

    function getNotifications() {
        Axios.get(`${ENDPOINT}notifications/user/?page=${page}`, {
            withCredentials: true
        }).then(
            res => {
                setNotifications([...notifications, ...res.data.notifications]);
                setPage(currentPage => currentPage + 1);
                setFetch(false);
            },
            err => console.log(err)
        );
    }
    async function readNotification(_id) {
        Axios(`${ENDPOINT}notifications/${_id}/read`, {
            method: "post",
            withCredentials: true
        }).then(
            res => {
                socket.emit("notification read", { _id }, err => {
                    console.log(err);
                });
            },
            err => console.log(err)
        );
    }

    useEffect(() => {
        getNotifications();
        socket.on("notification added", notification => {
            setNotifications(currentNotifications => [
                notification,
                ...currentNotifications
            ]);
        });
        socket.on("rerender notification", ({ _id }) => {
            setNotifications(currentNotifications => {
                const res = currentNotifications.map(notf =>
                    notf._id === _id ? { ...notf, isRead: true } : notf
                );
                return res;
            });
        });
    }, []);

    useEffect(() => {}, [notifications]);

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
