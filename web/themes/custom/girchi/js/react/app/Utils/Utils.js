import jwt from "jsonwebtoken";
import Axios from "axios";
import Cookies from "js-cookie";

export const generateJwtIfExpired = () => {
    let accessToken = Cookies.get("g-u-at");
    if (accessToken) {
        const { exp } = jwt.decode(accessToken);
        if (new Date().getTime() > exp * 1000) {
            Axios.get("/api/jwt/token_refresh").then(
                res => (accessToken = Cookies.get("g-u-at")),
                error => console.log(error)
            );
        }
        return accessToken;
    }
};
