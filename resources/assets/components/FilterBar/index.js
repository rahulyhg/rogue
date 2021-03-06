import React from 'react';
import PropTypes from 'prop-types';

import './filter-bar.scss';

class FilterBar extends React.Component {
  constructor() {
    super();

    this.state = {
      filters: {},
    };

    // The component provides a function the children components can use to update the state.
    this.updateFilters = this.updateFilters.bind(this);
  }

  updateFilters(values) {
    this.setState(previousState => {
      const newState = { ...previousState };
      newState.filters[Object.keys(values)] = Object.values(values)[0];
      return newState;
    });
  }

  render() {
    // ***Rendering the children**
    // This maps over any child of the FilterBar component and makes a copy of it so we can send props
    // (like our function to update state) to it from this component.
    const childrenWithProps = React.Children.map(this.props.children, child =>
      React.cloneElement(child, {
        updateFilters: this.updateFilters,
      }),
    );

    // Render the new children.
    return (
      <div className="filter-bar container__block">
        <h2 className="heading -gamma">Post Filters</h2>
        <div>{childrenWithProps}</div>
        <div className="container__block -third">
          <button
            className="button"
            onClick={() => this.props.onSubmit(this.state.filters)}
          >
            Apply Filters
          </button>
        </div>
      </div>
    );
  }
}

FilterBar.propTypes = {
  children: PropTypes.node,
  onSubmit: PropTypes.func.isRequired,
};

FilterBar.defaultProps = {
  children: null,
};

export default FilterBar;
