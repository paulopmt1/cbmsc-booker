import React from "react";

interface AvatarProps {
    imageSrc: string;
    altText?: string;
    size?: number; // padrão 100px
}

const Avatar: React.FC<AvatarProps> = ({ imageSrc, altText = "Avatar", size = 100 }) => {
    return (
        <div style={{ textAlign: "center", marginBottom: "20px" }}>
            <img
            src={imageSrc}
            alt={altText}
            style={{
                width: size,
                height: size,
                borderRadius: "50%",
                objectFit: "cover",
                boxShadow: "0 2px 8px rgba(0, 0, 0, 0.1)"
            }}
            />
        </div>
    );
};

export default Avatar;