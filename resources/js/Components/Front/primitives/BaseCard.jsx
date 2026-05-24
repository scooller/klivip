import { WaCard } from './wa';

export default function BaseCard({ title = null, className = '', children }) {
    const classes = ['casino-card', className].filter(Boolean).join(' ');

    return (
        <WaCard className={classes}>
            {title ? <h3 className="casino-card-title">{title}</h3> : null}
            <div className="casino-card-body">{children}</div>
        </WaCard>
    );
}
