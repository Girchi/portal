import React, { useState, useEffect } from "react";

const Notification = ({ notification, readNotification }) => {
    const { _id, title, desc, link, photoUrl, created, isRead } = notification;
    const [read, setRead] = useState(isRead);

    useEffect(() => {}, [read, setRead]);

    return (
        <a
            className={`row notifications-full-page ${
                !read ? "notifications-to-see" : ""
            }`}
            href={link}
            onClick={e => {
                e.preventDefault();
                readNotification(_id);
                if (!read) {
                    setRead(true);
                }
            }}
        >
            <div className="col-3 col-sm-2 col-xl-1 pr-0  pl-lg-2 pl-lg-4 d-flex justify-content-center align-items-center">
                <div className="notifications-full-page__img">
                    <img src={photoUrl} />
                </div>
            </div>
            <div className="buru col-7 col-sm-8 col-xl-10 notifications-full-page__text mr-0">
                <h4>{title}</h4>
                <p>{desc}</p>
                <span className="notify-time">{created}</span>
            </div>
            <div className="col-2 col-xl-1 notifications-full-page__corner-circle pl-0">
                <div></div>
            </div>
        </a>
    );
};

export default Notification;
