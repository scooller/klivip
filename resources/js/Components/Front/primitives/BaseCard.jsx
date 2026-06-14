export default function BaseCard({ title = null, className = '', children }) {
    const classes = ['casino-card', 'card', className].filter(Boolean).join(' ');

    return (
        <div className={classes}>
            {title ? <h3 className="casino-card-title card-header">{title}</h3> : null}
            <div className="casino-card-body card-body">{children}</div>
        </div>
    );
}
