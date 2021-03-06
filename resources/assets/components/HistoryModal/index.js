import React from 'react';
import { map, isEmpty } from 'lodash';
import PropTypes from 'prop-types';

import Empty from '../Empty';
import Table from '../Table';
import './history-modal.scss';

class HistoryModal extends React.Component {
  constructor() {
    super();

    this.state = {
      quantity: null,
    };

    this.onUpdate = this.onUpdate.bind(this);
    // @TODO: add this back in when we enable this function.
    // this.parseEventData = this.parseEventData.bind(this);
  }

  onUpdate(event) {
    let value = Number.isInteger(parseInt(event.target.value))
      ? event.target.value
      : null;

    this.setState({ quantity: value });
  }

  // @TODO: add this back in once we've updated the events system.
  // parseEventData(events) {
  //   const eventsWithChange = [];

  //   for (let i = 0; i < events.length; i++) {
  //     const current = events[i];
  //     const next = events[i + 1];

  //     if (next) {
  //       if (current.content.quantity != next.content.quantity || current.content.why_participated != next.content.why_participated || current.content.quantity_pending != next.content.quantity_pending) {
  //         // If there is a difference in the record, add to the log.
  //         eventsWithChange.push(current);
  //       }
  //     }
  //   }

  //   // Always include the first event in the response
  //   // so there is something in the table.
  //   // @TODO: change this when we start paginating.
  //   eventsWithChange.push(events[events.length - 1]);

  //   return eventsWithChange;
  // }

  render() {
    const signup = this.props.signup;
    const campaign = this.props.campaign;
    // const parsedEvents = ! isEmpty(this.props.signupEvents) ? this.parseEventData(this.props.signupEvents) : null;
    const post = this.props.post;

    return (
      <div className="modal">
        <a href="#" onClick={this.props.onClose} className="modal-close-button">
          &times;
        </a>
        <div className="modal__block">
          <h3>Change Quantity</h3>
          <div className="container__block -half">
            <h4>Old Quantity</h4>
            <p>{post.quantity} things done</p>
          </div>
          <div className="container__block -half">
            <h4>New Quantity</h4>
            <div className="form-item">
              <input
                type="text"
                onChange={this.onUpdate}
                className="text-field"
                placeholder="Enter # here"
              />
            </div>
          </div>
          <h3>📖 History 📖</h3>
          <p>
            {' '}
            <em>
              We're making some edits to the events log - it'll be back soon!
            </em>{' '}
          </p>
          {/* @TODO: add this back in when we've updated the events system and are ready to show events log.
          <div className="container">
            { ! isEmpty(parsedEvents) ?
              <div>
                <p>Below shows the 20 most recent changes to the member's signup. This includes changes to the quantity or why. If you need changes beyond the 20 listed here, please reach out to Team Bleed!</p>
                <Table headings={['Quantity', 'Why Participated', 'Updated At', 'User']} data={parsedEvents} type="events" />
              </div>
              :
              <Empty header="No History To Show!" copy="Sorry, but we don't have any history for this signup."/>
            }
          </div>
        */}
        </div>
        <button
          className="button -history"
          disabled={!this.state.quantity}
          onClick={() => this.props.onUpdate(post, this.state.quantity)}
        >
          Save
        </button>
      </div>
    );
  }
}

HistoryModal.propTypes = {
  campaign: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  onClose: PropTypes.func.isRequired,
  onUpdate: PropTypes.func.isRequired,
  signup: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  signupEvents: PropTypes.array.isRequired, // eslint-disable-line react/forbid-prop-types
};

export default HistoryModal;
